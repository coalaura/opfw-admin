function escapeXml(unsafe) {
	return unsafe.toString().replace(/[<>&'"]/g, c => {
		switch (c) {
			case "<":
				return "&lt;";
			case ">":
				return "&gt;";
			case "&":
				return "&amp;";
			case "'":
				return "&apos;";
			case '"':
				return "&quot;";
			default:
				return c;
		}
	});
}

function generateExcelXml(rows) {
	let xml = `<?xml version="1.0"?>
<?mso-application progid="Excel.Sheet"?>
<Workbook xmlns="urn:schemas-microsoft-com:office:spreadsheet"
	xmlns:o="urn:schemas-microsoft-com:office:office"
	xmlns:x="urn:schemas-microsoft-com:office:excel"
	xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet">
<Worksheet ss:Name="Sheet1">
<Table>`;

	for (const row of rows) {
		xml += "<Row>";

		for (const cell of row) {
			xml += `<Cell><Data ss:Type="String">${escapeXml(cell)}</Data></Cell>`;
		}

		xml += "</Row>";
	}

	xml += `</Table>
</Worksheet>
</Workbook>`;

	return xml;
}

function downloadExcel(name, rows) {
	const xml = generateExcelXml(rows),
		blob = new Blob([xml], { type: "application/vnd.ms-excel" }),
		url = URL.createObjectURL(blob);

	const a = document.createElement("a");

	a.href = url;
	a.download = `${name}.xls`;

	document.body.appendChild(a);

	a.click();

	document.body.removeChild(a);
	URL.revokeObjectURL(url);
}

const Spreadsheet = {
	async install(Vue, options) {
		Vue.prototype.createSpreadsheet = (name, rows) => {
			downloadExcel(name, rows);
		};
	},
};

export default Spreadsheet;
