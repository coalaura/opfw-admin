#!/bin/bash

set -e

quiet=0

for arg in "$@"; do
    case $arg in
        --quiet)
            quiet=1
            shift
            ;;
    esac
done

status() {
	echo $1 > update
}

join_by() {
	local IFS="$1"
	shift
	echo "$*"
}

write_done() {
	(
		flock 200
		echo $1 >> .done
	) 200>.done.lock
}

trap 'rm -f .done .done.lock update' EXIT

rm -f .done .done.lock

status "pulling"

git stash
git pull

status "migrating"

clusters=()

total=0
completed=0
failures=0

touch .done

for directory in ./envs/c*/; do
    [ -L "${directory%/}" ] && continue

    cluster="$(basename -- "$directory")"

    total=$((total + 1))
	clusters+=("$cluster")

    (
        if timeout "45s" php artisan migrate --cluster="$cluster" --force > /dev/null 2>&1; then
            write_done "success $cluster"
        else
            write_done "fail $cluster"
        fi
    ) &
done

timer=45

while (( completed < total )); do
    sleep 1

	timer=$((timer - 1))

    mapfile -t done_lines < .done 2>/dev/null || done_lines=()

    completed=${#done_lines[@]}

    pending_clusters=("${clusters[@]}")

    for line in "${done_lines[@]}"; do
        read -r _ cluster_name <<< "$line"

        for i in "${!pending_clusters[@]}"; do
            if [[ "${pending_clusters[i]}" == "$cluster_name" ]]; then
                unset 'pending_clusters[i]'
            fi
        done
    done

    printf "\rCompleted: %d/%d - [%s] - %ds$(tput el)" "$completed" "$total" "$(join_by ', ' "${pending_clusters[@]}")", "$timer"
done

echo

while read -r result cluster; do
    if [[ $result == "fail" ]]; then
        failures=$((failures + 1))

        echo "Migration failed for $cluster"
    fi
done < .done

if (( failures > 0 )); then
    echo "$failures migrations failed."

    if (( quiet == 0 )); then
        exit 1
    fi
else
    echo "All migrations completed successfully."
fi

status "done"