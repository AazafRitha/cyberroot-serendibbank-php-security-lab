"""Build the editable Word template from docs/reporting/vapt-report-template.md."""

from pathlib import Path
import re

from docx import Document
from docx.enum.table import WD_CELL_VERTICAL_ALIGNMENT
from docx.enum.text import WD_ALIGN_PARAGRAPH
from docx.oxml import OxmlElement
from docx.oxml.ns import qn
from docx.shared import Inches, Pt, RGBColor


ROOT = Path(__file__).resolve().parents[1]
SOURCE = ROOT / "docs" / "reporting" / "vapt-report-template.md"
OUTPUT = ROOT / "docs" / "reporting" / "SerendibBank-VAPT-Report-Template.docx"


def plain(value: str) -> str:
    value = re.sub(r"\[([^]]+)\]\([^)]+\)", r"\1", value)
    return value.replace("**", "").replace("`", "")


def add_inline(paragraph, value: str) -> None:
    value = re.sub(r"\[([^]]+)\]\([^)]+\)", r"\1", value).replace("`", "")
    for index, chunk in enumerate(value.split("**")):
        if chunk:
            run = paragraph.add_run(chunk)
            run.bold = index % 2 == 1


def set_cell_format(cell, fill: str | None = None) -> None:
    tc_pr = cell._tc.get_or_add_tcPr()
    mar = OxmlElement("w:tcMar")
    for edge, width in (("top", "105"), ("bottom", "105"), ("left", "120"), ("right", "120")):
        node = OxmlElement(f"w:{edge}")
        node.set(qn("w:w"), width)
        node.set(qn("w:type"), "dxa")
        mar.append(node)
    tc_pr.append(mar)
    borders = OxmlElement("w:tcBorders")
    for edge in ("top", "left", "bottom", "right"):
        node = OxmlElement(f"w:{edge}")
        node.set(qn("w:val"), "single")
        node.set(qn("w:sz"), "4")
        node.set(qn("w:color"), "D9D9D9")
        borders.append(node)
    tc_pr.append(borders)
    if fill:
        shade = OxmlElement("w:shd")
        shade.set(qn("w:fill"), fill)
        tc_pr.append(shade)


def table_widths(headers: list[str]) -> list[float]:
    if headers == ["Version", "Date", "Author", "Change and review status"]:
        return [.7, 1.0, 1.1, 3.9]
    if len(headers) == 6:
        return [1.05, 1.05, 1.05, 1.05, 1.35, 1.15]
    if headers[0] == "Asset or service":
        return [1.55, 2.65, 2.5]
    if headers[0] == "Test area":
        return [2.65, 1.45, 2.6]
    if headers[0] == "ID":
        return [.6, 2.25, .9, 1.6, 1.35]
    if headers[0] == "Evidence ID":
        return [.9, 1.8, 2.8, 1.2]
    if len(headers) == 2:
        return [2.05, 4.65]
    return [6.7 / len(headers)] * len(headers)


doc = Document()
section = doc.sections[0]
section.page_width = Inches(8.5)
section.page_height = Inches(11)
section.top_margin = Inches(.72)
section.bottom_margin = Inches(.68)
section.left_margin = Inches(.9)
section.right_margin = Inches(.9)
section.header_distance = Inches(.34)
section.footer_distance = Inches(.34)

normal = doc.styles["Normal"]
normal.font.name = "Aptos"
normal.font.size = Pt(10)
normal.font.color.rgb = RGBColor(40, 40, 40)
normal.paragraph_format.line_spacing = 1.12
normal.paragraph_format.space_after = Pt(7)
for name, size, before, after in (("Title", 22, 0, 13), ("Heading 1", 14, 18, 7), ("Heading 2", 11.5, 13, 5)):
    style = doc.styles[name]
    style.font.name = "Aptos Display" if name == "Title" else "Aptos"
    style.font.size = Pt(size)
    style.font.bold = name != "Title"
    style.font.color.rgb = RGBColor(0, 0, 0)
    style.paragraph_format.space_before = Pt(before)
    style.paragraph_format.space_after = Pt(after)
    style.paragraph_format.keep_with_next = True
    if name == "Title":
        ppr = style._element.get_or_add_pPr()
        for element in ppr.findall(qn("w:pBdr")):
            ppr.remove(element)

header = section.header.paragraphs[0]
header.text = "SERENDIBBANK  /  WEB APPLICATION VAPT REPORT"
header.style = normal
for run in header.runs:
    run.font.size = Pt(8)
    run.font.bold = True
    run.font.color.rgb = RGBColor(88, 65, 72)
footer = section.footer.paragraphs[0]
footer.alignment = WD_ALIGN_PARAGRAPH.RIGHT
run = footer.add_run("Confidential working template  •  Page ")
run.font.size = Pt(8)
run.font.color.rgb = RGBColor(110, 110, 110)
field = OxmlElement("w:fldSimple")
field.set(qn("w:instr"), "PAGE")
footer._p.append(field)

lines = SOURCE.read_text(encoding="utf-8").splitlines()
index = 0
after_table = False
while index < len(lines):
    raw = lines[index].strip()
    if not raw or raw == "---":
        index += 1
        continue
    if raw.startswith("| "):
        block = []
        while index < len(lines) and lines[index].strip().startswith("|"):
            cells = [plain(part.strip()) for part in lines[index].strip().strip("|").split("|")]
            if not all(re.fullmatch(r"[-: ]+", cell) for cell in cells):
                block.append(cells)
            index += 1
        widths = table_widths(block[0])
        table = doc.add_table(rows=len(block), cols=len(block[0]))
        table.autofit = False
        for row_index, values in enumerate(block):
            for col_index, value in enumerate(values):
                cell = table.rows[row_index].cells[col_index]
                cell.width = Inches(widths[col_index])
                cell.vertical_alignment = WD_CELL_VERTICAL_ALIGNMENT.CENTER
                cell.text = value
                set_cell_format(cell, "38343A" if row_index == 0 else ("F6F4F4" if row_index % 2 == 0 else None))
                for paragraph in cell.paragraphs:
                    paragraph.paragraph_format.space_after = Pt(0)
                    paragraph.paragraph_format.line_spacing = 1.08
                    if row_index == 0:
                        paragraph.paragraph_format.keep_with_next = True
                    for run in paragraph.runs:
                        run.font.size = Pt(8.7)
                        if row_index == 0:
                            run.font.bold = True
                            run.font.color.rgb = RGBColor(255, 255, 255)
        repeat = OxmlElement("w:tblHeader")
        repeat.set(qn("w:val"), "true")
        table.rows[0]._tr.get_or_add_trPr().append(repeat)
        after_table = True
        continue
    if raw.startswith("# "):
        doc.add_paragraph(raw[2:], style="Title")
    elif raw.startswith("## "):
        paragraph = doc.add_paragraph(raw[3:], style="Heading 1")
        if raw.startswith("## 6. Detailed finding"):
            paragraph.paragraph_format.page_break_before = True
    elif raw.startswith("### "):
        doc.add_paragraph(raw[4:], style="Heading 2")
    elif re.match(r"^\d+\. ", raw):
        doc.add_paragraph(plain(re.sub(r"^\d+\. ", "", raw)), style="List Number")
    elif raw.startswith("- "):
        doc.add_paragraph(plain(raw[2:]), style="List Bullet")
    else:
        paragraph = doc.add_paragraph()
        add_inline(paragraph, raw)
        if raw.startswith("**Report status:**") or raw.startswith("**Classification:**") or raw.startswith("**Prepared for:**") or raw.startswith("**Prepared by:**"):
            paragraph.paragraph_format.space_after = Pt(3)
        elif after_table:
            paragraph.paragraph_format.space_before = Pt(8)
    after_table = False
    index += 1

doc.core_properties.title = "SerendibBank Web Application VAPT Report Template"
doc.core_properties.subject = "Editable assessment report and finding record"
doc.core_properties.author = "CyberRoot"
doc.save(OUTPUT)
print(OUTPUT)
