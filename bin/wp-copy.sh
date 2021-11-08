#!/bin/bash
set -euo pipefail

if [[ $# -lt 2 ]] ; then
	echo "Benutzung: $0 [Quellsemester] [Quellseite] [[Zielsemester]]" >&2
	echo >&2
	echo "Beispiel:" >&2
	echo "	Anzeigen des SPiC Seitenbaums im WS21" >&2
	echo "	$0 ws21 spic" >&2
	echo >&2
	echo "	Kopieren des SPiC Seitenbaums vom WS21 zum SS22" >&2
	echo "	$0 ws21 spic ss22" >&2
	exit 0
fi


# Herunterladen von wp-cli
if [[ ! -e "wp-cli.phar" ]] ; then
	echo "Download von wp-cli"
	curl -O https://raw.githubusercontent.com/wp-cli/builds/gh-pages/phar/wp-cli.phar
fi

function wp-cli() {
	# Debug: echo wp-cli $@ >&2
	sudo -u www-data php wp-cli.phar --path=/var/www/wordpress $@
}

# Testen
wp-cli cli version >&2

# Page ID der Oberseite "Lehre"
TEACHING_ID=311

# ID des Quellsemesters herausfinden
source_sem_id=$(wp-cli post list --name="$1" --post_parent=$TEACHING_ID --post_type=page --format=csv --fields=ID | tail -n+2)
if [[ $source_sem_id =~ ^[0-9]+$ ]] ; then
	echo " - Quellsemester $1 ID: $source_sem_id"
else
	echo "Ungueltige Quellsemester ID ($source_sem_id) fuer '$1' - abbruch!" >&2
	exit 1
fi

# ID der Quellseite herausfinden
source_id=$(wp-cli post list --name="$2" --post_parent=$source_sem_id --post_type=page --format=csv --fields=ID | tail -n+2)
if [[ $source_id =~ ^[0-9]+$ ]] ; then
	echo " - Quellseiten $2 ID: $source_id"
else
	echo "Ungueltige Quellseiten ID ($source_id) fuer '$2' - abbruch!" >&2
	exit 1
fi

# Zielseite
target_sem_id=""
if [[ $# -ge 3 && -n "$3" ]] ; then
	# ID der Zielseite herausfinden
	target_sem_id=$(wp-cli post list --name="$3" --post_parent=$TEACHING_ID --post_type=page --format=csv --fields=ID | tail -n+2)
	if [[ $target_sem_id =~ ^[0-9]+$ ]] ; then
		echo " - Zielsemester $3 ID: $target_sem_id"
	else
		echo "Ungueltige Zielsemester ID ($target_sem_id) fuer '$3' - abbruch!" >&2
		exit 1
	fi
fi


id=32
# Rekursives kopieren
function rekursion() {
	# $1 ist die Quelle
	# $2 ist das Ziel
	# $3 sind Einrückungen (Leerzeichen) zur visualisierung der Rekursionstiefe in der Ausgabe

	# URL-Name der Quellseite
	local name=$(wp-cli post get $1 --format=csv --fields=post_name | tail -n-1 | cut -d',' -f2)

	# Ausgabe
	echo "$3$1: $name" >&2

	# ggf Kopie erstellen
	local duplicate_id=""
	if [[ -n "$2" ]] ; then
		# Prüfen, ob URL-Name bereits unterhalb der Zielseite existiert
		if [[ $(wp-cli post list --name="$name" --post_parent=$2 --post_type=page --format=csv --fields=ID | wc -l) -ne 1 ]] ; then
			echo "Es gibt bereits '$name' als Kind von ID $2 - abbruch!" >&2
			exit 1
		fi

		# Duplikat erstellen
		duplicate_id=$(wp-cli post create --post_type=page --post_parent=$2 --from-post=$1 | sed -e 's/^.* Created post \([0-9]*\)./\1/')
		if [[ $duplicate_id =~ ^[0-9]+$ ]] ; then
			echo "$3-> $duplicate_id (neue Kopie auf Eltern ID $2)"
		else
			echo "Ungueltige Duplikats-ID ($duplicate_id) fuer Quell-ID $1 nach ID $2 - abbruch!" >&2
			exit 1
		fi
		# Die neu erstellte Seite (duplicate_id) ist nun das Ziel für Kopien der Kinder
	fi

	# Alle Kinder der Quelle durchgehen
	local children=( $(wp-cli post list --post_parent="$1" --post_type=page --format=csv --fields=ID  | tail -n+2) )
	for child in "${children[@]}" ; do
		# Rekursiver Abstieg
		rekursion "$child" "$duplicate_id" "$3  "
	done
}
echo "Seitenbaum:"
rekursion "$source_id" "$target_sem_id" "  "

