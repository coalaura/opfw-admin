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

can_expect=0; command -v expect >/dev/null 2>&1 && can_expect=1

status() {
	echo $1 > update
}

join_by() {
    local d="$1"

    shift

    local first=1

    for arg; do
        if (( first )); then
            printf "%s" "$arg"

            first=0
        else
            printf "%s%s" "$d" "$arg"
        fi
    done
}

write_done() {
	(
		flock 200
		echo $1 >> .done
	) 200>.done.lock
}

run_migration_timeout() {
    local cluster="$1"
    local migration_timeout="20"

    if (( can_expect )); then
        expect -c "
            log_user 0
            set timeout $migration_timeout
            spawn php artisan migrate --cluster=\"$cluster\" --force
            expect {
                eof { exit 0 }
                timeout { exit 124 }
            }
        " > /dev/null 2>&1
    else
        timeout "$migration_timeout" php artisan migrate --cluster="$cluster" --force > /dev/null 2>&1
    fi
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
		if run_migration_timeout "$cluster"; then
			write_done "success $cluster"
		else
			code=$?

			if [[ $code -eq 124 ]]; then
				write_done "timeout $cluster"
			else
				write_done "fail $cluster"
			fi
		fi
	) &
done

clusters=( $(printf "%s\n" "${clusters[@]}" | sort -V) )

while (( completed < total )); do
    sleep 0.5

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

	tmp=()

	for c in "${pending_clusters[@]}"; do
		[[ -n "$c" ]] && tmp+=("$c")
	done

	pending_clusters=("${tmp[@]}")

    printf "\rCompleted: %d/%d - [%s]$(tput el)" "$completed" "$total" "$(join_by ', ' "${pending_clusters[@]}")"
done

echo

while read -r result cluster; do
    case "$result" in
        fail)
            failures=$((failures + 1))

            echo "Migration failed for $cluster"
            ;;
        timeout)
            failures=$((failures + 1))

            echo "Migration timed out for $cluster"
            ;;
    esac
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