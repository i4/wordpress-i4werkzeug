# Supports arbitrary perlcode
# If you want, you can for instance call your password manager here, if desired
# e.g.:
#   $password = `gopass --password sys.cs.fau.de`;
#   die "gopass failed" unless $? == 0;

$user     = "peter";
$password = 'peter';
$url      = "https://sys.cs.fau.de";
#  $basicauth_user     = undef; # Optional, defaults to $user
#  $basicauth_password = undef; # Optional, leave undef to not enable extra basicauth
