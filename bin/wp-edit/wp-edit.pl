#!/usr/bin/env perl

use warnings;
use strict;

# To enable debugging, uncomment the debug blocks below
# This will enable verbose logging of all requests

use Data::Dumper;
use Path::Tiny;
use IPC::Run qw(run);
### DEBUG
#use Log::ger::App;
### /DEBUG
use JSON;
use URI;
use File::Basename;
use Linux::Inotify2;
use Getopt::Long;
use Pod::Usage;

my $CONFFILE = path("@{[$ENV{XDG_CONFIG_DIR} // '~/.config']}/i4wp/credentials.pm");
# my $CONFFILE = path("./credentials");
# File should define:
#  $user = "....";
#  $password = "....";
#  $url = "....";
#  $basicauth_user     = undef; # Optional, defaults to $user
#  $basicauth_password = undef; # Optional, leave undef to not enable extra basicauth

package WPRestClient {
	use warnings;
	use strict;

	use Class::Tiny qw(baseurl domain);

	use JSON;
	use LWP::UserAgent;
	### DEBUG
	# use Log::ger::For::LWP
	# 	-log_request_body => 1,
	# 	-log_response_body => 1,
	# 	-decode_response_body => 0,
	# ;
	### /DEBUG
	use URI;


	sub BUILD {
		my ($self, $args) = @_;

		$self->{ua} = LWP::UserAgent->new();
		$self->{ua}->cookie_jar({});

		my ($user, $password, $url, $basicauth_user, $basicauth_password);
		eval $CONFFILE->slurp();

		$self->{user} = $user;
		$self->{password} = $password;
		$self->{domain} = $url;
		$self->{baseurl} = "$url/wp-json/wp/v2/";

		if (defined($basicauth_password)) {
			my $uri = URI->new($url);
			$self->{ua}->credentials($uri->host . ":" . $uri->port, "i4wordpress", $basicauth_user // $user, $basicauth_password);
		}
	}

	sub cleanup_jsonheader {
		my ($str) = @_;

		$str =~ s/^\s*<p><\/p>$//gmr;
	}

	sub deserialize {
		my ($str) = @_;

		decode_json(cleanup_jsonheader($str));
	}

	sub get_paginated {
		my ($self, $endpoint, $params) = @_;
		$params //= {};

		my %cpy = %$params;
		# 100 is wordpresses current per page limit
		$cpy{per_page} = 100;

		my $done;
		my $page=1;
		my @entries;
		do {
			my $url = URI->new($self->{baseurl} . "$endpoint");
			$url->query_form(
				page => $page,
				%cpy
			);
			my $res = $self->{ua}->get($url->canonical);
			if (!$res->is_success) {
				die $res->status_line;
			}
			my $total = $res->header('X-WP-TotalPages');
			$done = ($page >= $total);
			$page++;

			push @entries,  @{deserialize($res->decoded_content)};
		} while(!$done);

		return \@entries;
	}

	sub get {
		my ($self, $endpoint, $params) = @_;
		$params //= {};
		my $url = URI->new($self->{baseurl} . "$endpoint");
		$url->query_form(%$params);
		my $res = $self->{ua}->get($url->canonical);
		if (!$res->is_success) {
			die $res->status_line;
		}
		return deserialize($res->decoded_content);
	}

	sub post_json {
		my ($self, $endpoint, $content, $params) = @_;
		my $url = URI->new($self->{baseurl} . "$endpoint");
		$params //= {};
		$url->query_form(
			%$params
		);
		my $res = $self->{ua}->post(
			$url->canonical,
			'Content-type' => 'application/json',
			Content => encode_json($content)
		);
		if (!$res->is_success) {
			die $res->status_line;
		}
		return $res->decoded_content;
	}

	sub retrieve_nonce {
		my ($self) = @_;

		# Ok, this is outright hacky...
		my $url = URI->new($self->{domain} . "/wp-admin/post-new.php");
		my $res = $self->{ua}->get($url);
		if (!$res->is_success) {
			die $res->status_line;
		}
		my $output = $res->decoded_content;

		if ($output =~ /^var\swpApiSettings\s*=\s*(.*);\s*$/m) {
			return decode_json($1)->{nonce};
		} else {
			die "Failed to retieve nonce";
		}

	}

	sub auth {
		my ($self) = @_;

		my $url = URI->new($self->{domain} . "/wp-login.php");
		my $res = $self->{ua}->post($url->canonical, {
				log => $self->{user},
				pwd => $self->{password},
			});
		if ((!$res->is_success) && ($res->code != 302)) { # Redirects are fine here
			die $res->status_line;
		}
		# nothing else to do here: we are only interested in the Cookie...

		$self->{nonce} = $self->retrieve_nonce();
		$self->{ua}->default_header("X-WP-Nonce" => $self->{nonce});
		print "Using nonce: @{[$self->{nonce}]}\n";
	}

}

sub get_pagelist {
	my ($client) = @_;

	my $pages = $client->get_paginated("pages", {_fields => "id,link,slug"});
	my %list = map {
			my $url = $_->{link} =~ s/^\Q@{[$client->domain]}\E//r;
			$_->{id}, $url
		} @$pages;

	return %list;
}

sub pagenames_to_id {
	my ($client, $names) = @_;

	my %list = get_pagelist($client);
	my %inv = reverse %list;

	my @ids = map {
		my $id = $inv{$_};
		die "Failed to translate $_ to wordpress id (check spelling?)" unless defined($id);
		$id
	} @$names;
	return @ids;
}

sub prompt_pages {
	my ($client) = @_;

	my %pages = get_pagelist($client);
	my $list = join("\n", map { "$_\t$pages{$_}" } (keys %pages));

	my $out;
	run [qw(fzf --multi --delimiter=\t --with-nth=2)], '<', \$list, '>', \$out or die "fzf failed";
	my @lines = split(/\n/, $out);
	my @pages;
	for my $line (@lines) {
		if ($line =~ /^(\d+)\s/) {
			push @pages, $1;
		} else {
			die "FZF output format error";
		}
	}
	return @pages;
}

sub get_page {
	my ($client, $id) = @_;
	$client->get("pages/$id", {context => 'edit'});
}

sub update_content {
	my ($client, $id, $new_content) = @_;
	$client->post_json("pages/$id", {content => $new_content}, { context => 'edit'});

}

sub create_filename_template {
	my ($path) = @_;
	$path =~ s/^\///g;
	$path =~ s/\//_/g;
	return "$path-XXXXXXXX";
}

sub create_local_copy {
	my ($wp, $id, $cleanup) = @_;

	my $page = get_page($wp, $id);
	my $uri = URI->new($page->{link});
	my $file = Path::Tiny->tempfile(TEMPLATE => create_filename_template($uri->path), UNLINK => $cleanup, SUFFIX => ".html");
	my $content = get_page($wp, $id)->{content}->{raw};
	die "Did not receive wordpress content" unless defined($content);
	$file->spew_utf8($content);
	return $file;
}

sub run_inotify {
	my ($client, $local_copies, $edit) = @_;

	my %by_basename;
	$by_basename{$local_copies->{$_}->basename} = $_ for keys %$local_copies;

	my $inotify = Linux::Inotify2->new
		or die "Unable to create new inotify object: $!";

	for my $watch (keys %$local_copies) {
		$inotify->watch("$local_copies->{$watch}", IN_CLOSE_WRITE | IN_DELETE_SELF)
			or die "Watch creation failed: $local_copies->{watch}: $!";
	}

	print "\$EDITOR @{[values %$local_copies]}\n";
	my $pid = 0; # Default, if we do not fork we want to run the inotifyloop anyways
	if ($edit) {
		$pid = fork();
		die "Fork failed $!" if not defined $pid;
	}

	if ($pid == 0) {
		# See: https://stackoverflow.com/a/18494819 by amon under CC-BY-SA 3.0
		my $run = 1;
		local $SIG{INT} = sub { $run = 0 };
		while ($run) {
			my @events = $inotify->read;
			for my $e (@events) {
				die "Delete action found for file: @{[$e->fullname]}" if $e->IN_DELETE_SELF;
				if ($e->IN_CLOSE_WRITE) {
					my $path = path($e->fullname);
					my $id = $by_basename{$path->basename};
					die "Bad inotify event for unknown file: $path without associated id" unless defined($id);
					print " Updating $path (id: $id)" . ($edit ? "" : "\n"); # Newlines distort vim/nano etc...
					STDOUT->flush();
					update_content($client, $id, $path->slurp_utf8);
				}
			}
		}
	} else {
		my $retval = system($ENV{EDITOR}, values %$local_copies);
		if ($retval != 0) {
			# Error, we might want to prompt, so that the user will know, so that they kan save the local copies if desired
			print STDERR "Edit session closed with nonzero exitcode! You might want to save your work in case of errors\n";
			print STDERR "Files: @{[values %$local_copies]}\n";
			print STDERR "Press ENTER when done\n";
			chomp(my $prompt = <>);
		}
		# Now terminate the child process
		kill 'INT' => $pid;
		waitpid($pid, 0);
	}
}

sub parse_cli {
	local @ARGV = @_;

	my $help = 0;

	my %opts = (
		edit => 1,
		cleanup => 1,
	);

	GetOptions(
		'edit|e!' => \$opts{edit},
		'help|h' => \$help,
		'cleanup|c!' => \$opts{cleanup},
	) or pos2usage(2);

	pod2usage(0) if $help;

	$opts{pages} = \@ARGV;

	return \%opts;
}

sub main {
	my $opts = parse_cli(@_);

	my @pages = @{$opts->{pages}};

	my $wp = WPRestClient->new();
	$wp->auth();
	if (@pages) {
		@pages = pagenames_to_id($wp, \@pages);
	} else {
		@pages = prompt_pages($wp);
	}

	my %files;
	$files{$_} = create_local_copy($wp, $_, $opts->{cleanup}) for @pages;

	run_inotify($wp, \%files, $opts->{edit});
}

main(@ARGV);

__END__

=head1 NAME

wp-edit: edit your (already existing) WordPress page from the safety of local editor

=head1 SYNOPSIS

wp-edit [options] [uri1 uri2 ...]

 Options:
    -h|-help               print usage
    --edit|--noedit        whether to directly spawn $EDITOR for editing (default: true)
    --cleanup|--nocleanup  whether to remove the local, temporary copies of edited files (default: true) after closing

URIs take the form of url slugs:

	wp-edit lehre/ezs/uebung will edit https://$wordpressurl/lehre/ezs/uebung

If no URIs are specified, wp-edit spawns FZF for interactive selection. Multiple entries can be selected at once using the tab-key, see C<man fzf>.

When a page was selected, F<wp-edit.pl> retrieves its contents. From then on, whenever the page is saved locally (detected using Inotify), the new version is pushed to the wordpress instance and published.

wp-edit aquires login-credentials from F<$XDG_CONFIG_DIR/i4wp/credentials.pm>. See the F<credentials-sample.pm> file in the repo for syntax.

As the script uses Inotify-wait, it probably only works on Linux, patches welcome.

=head1 INSTALLATION

Copy the included F<credentials-sample> to F<$XDG_CONFIG_DIR/i4wp/credentials.pm> and adapt for your user.

Be sure to have a properly configured C<$EDITOR> variable pointing to the editor of your choice.

Easiest way to install the required dependencies is probably to use carton:

	# install carton, e.g. apt install carton
	carton install
	carton exec -- ./wp-edit.pl

If you want to use your distributions libraries, you can find a list of the required perl-libs in the included F<cpanfile>; as those are in widespread used, they should be commonly available. Patches with lists for different distributions are welcome.

To use the fuzzy-finder--based page selection, fzf is required as an optional dependency.

=cut
