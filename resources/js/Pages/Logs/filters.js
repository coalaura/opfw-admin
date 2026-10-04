export function readLogFilters(filters, root) {
	const snapshot = { ...filters };

	for (const boundary of ['before', 'after']) {
		const date = root.querySelector(`#${boundary}-date`).value,
			time = root.querySelector(`#${boundary}-time`).value || (boundary === 'before' ? '00:00' : '23:59'),
			timestamp = date ? Math.round(new Date(`${date}T${time}`).getTime() / 1000) : NaN,
			originalTimestamp = Number(filters[boundary]);

		snapshot[boundary] = Number.isNaN(timestamp) ? null : timestamp;

		// Keep seconds from existing filters when their minute-only inputs have not changed.
		if (timestamp === Math.floor(originalTimestamp / 60) * 60) {
			snapshot[boundary] = originalTimestamp;
		}
	}

	return snapshot;
}

export function fillLogDates(filters, root) {
	for (const boundary of ['before', 'after']) {
		if (!filters[boundary]) {
			continue;
		}

		const date = new Date(filters[boundary] * 1000),
			month = String(date.getMonth() + 1).padStart(2, '0'),
			day = String(date.getDate()).padStart(2, '0'),
			hours = String(date.getHours()).padStart(2, '0'),
			minutes = String(date.getMinutes()).padStart(2, '0');

		root.querySelector(`#${boundary}-date`).value = `${date.getFullYear()}-${month}-${day}`;
		root.querySelector(`#${boundary}-time`).value = `${hours}:${minutes}`;
	}
}
