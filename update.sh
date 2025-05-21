#!/bin/bash

status() {
	echo $1 > update
}

trap 'rm -f .done update' EXIT

status "pulling"

git stash
git pull

status "migrating"

total=0
completed=0
failures=0

declare -A pids

for directory in ./envs/c*/; do
    [ -L "${directory%/}" ] && continue

    total=$((total + 1))
    cluster="$(basename -- "$directory")"

    (
        echo "Migrating $cluster"

        if php artisan migrate --cluster="$cluster" --force; then
            echo "success $cluster" >> .done
        else
            echo "fail $cluster" >> .done
        fi
    ) &

    pids[$!]=$cluster
done

for pid in "${!pids[@]}"; do
    wait "$pid"
done

while read -r result cluster; do
    if [[ $result == "success" ]]; then
        completed=$((completed + 1))
    else
        failures=$((failures + 1))

        echo "Migration failed for $cluster"
    fi
done < .done

echo "Completed: $completed/$total"

if (( failures > 0 )); then
    echo "Failures: $failures"

    exit 1
fi

status "done"