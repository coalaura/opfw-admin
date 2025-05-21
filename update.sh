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

for directory in ./envs/c*/; do
    [ -L "${directory%/}" ] && continue

    total=$((total + 1))
    cluster="$(basename -- "$directory")"

    (
        if php artisan migrate --cluster="$cluster" --force > /dev/null 2>&1; then
            echo "success $cluster" >> .done
        else
            echo "fail $cluster" >> .done
        fi
    ) &
done

while (( completed < total )); do
    sleep 0.2

    completed=$(grep -c '^' .done 2>/dev/null || echo 0)

    echo -ne "Progress: $completed/$total migrations complete\r"
done

while read -r result cluster; do
    if [[ $result == "fail" ]]; then
        failures=$((failures + 1))

        echo "Migration failed for $cluster"
    fi
done < .done

if (( failures > 0 )); then
    echo "$failures migrations failed."
    exit 1
else
    echo "All migrations completed successfully."
fi

status "done"