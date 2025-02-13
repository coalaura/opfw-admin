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

function renderRow(row, style = false) {
	let xml = "<Row>";

	for (const cell of row) {
		xml += `<Cell ss:StyleID="${style || "Default"}"><Data ss:Type="String">${escapeXml(cell)}</Data></Cell>`;
	}

	xml += "</Row>";

	return xml;
}

function generateExcelXml(sheet, header, rows) {
	let xml = `<?xml version="1.0"?>
<?mso-application progid="Excel.Sheet"?>
<Workbook xmlns="urn:schemas-microsoft-com:office:spreadsheet"
	xmlns:o="urn:schemas-microsoft-com:office:office"
	xmlns:x="urn:schemas-microsoft-com:office:excel"
	xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet">
<Styles>
	<Style ss:ID="Default" ss:Name="Normal">
		<Font ss:FontName="Montserrat"/>
    </Style>
    <Style ss:ID="HeaderStyle" ss:Parent="Default">
		<Borders>
			<Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1"/>
		</Borders>
    </Style>
</Styles>
<Worksheet ss:Name="${sheet}">
<Table>`;

	xml += renderRow(header, "HeaderStyle");

	for (const row of rows) {
		xml += renderRow(row);
	}

	xml += `</Table>
</Worksheet>
</Workbook>`;

	return xml;
}

function downloadExcel(file, sheet, header, rows) {
	const xml = generateExcelXml(sheet, header, rows),
		blob = new Blob([xml], { type: "application/vnd.ms-excel" }),
		url = URL.createObjectURL(blob);

	const a = document.createElement("a");

	a.href = url;
	a.download = file;

	document.body.appendChild(a);

	a.click();

	document.body.removeChild(a);
	URL.revokeObjectURL(url);
}

const Spreadsheet = {
	async install(Vue, options) {
		Vue.prototype.createSpreadsheet = (file, sheet, header, rows) => {
			downloadExcel(file, sheet, header, rows);
		};
	},
};

export default Spreadsheet;
