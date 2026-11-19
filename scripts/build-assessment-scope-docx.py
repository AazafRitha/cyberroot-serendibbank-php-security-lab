"""Build the shareable Word copy of docs/assessment-scope.md."""

from pathlib import Path
import re

from docx import Document
from docx.enum.table import WD_CELL_VERTICAL_ALIGNMENT
from docx.enum.text import WD_ALIGN_PARAGRAPH
from docx.oxml import OxmlElement
from docx.oxml.ns import qn
from docx.shared import Inches, Pt, RGBColor


ROOT = Path(__file__).resolve().parents[1]
SOURCE = ROOT / "docs" / "assessment-scope.md"
OUTPUT = ROOT / "docs" / "assessment-scope.docx"


def plain(text: str) -> str:
    text = re.sub(r"\[([^]]+)\]\([^)]+\)", r"\1", text)
    text = text.replace("**", "").replace("`", "")
    return text


def shade(cell, fill: str):
    tc_pr = cell._tc.get_or_add_tcPr()
    shd = OxmlElement("w:shd")
    shd.set(qn("w:fill"), fill)
    tc_pr.append(shd)


def border(cell):
    tc_pr = cell._tc.get_or_add_tcPr()
    borders = tc_pr.first_child_found_in("w:tcBorders")
    if borders is None:
        borders = OxmlElement("w:tcBorders")
        tc_pr.append(borders)
    for edge in ("top", "left", "bottom", "right"):
        tag = OxmlElement(f"w:{edge}")
        tag.set(qn("w:val"), "single")
        tag.set(qn("w:sz"), "4")
        tag.set(qn("w:color"), "D9D9D9")
        borders.append(tag)


def padding(cell):
    tc_pr = cell._tc.get_or_add_tcPr()
    mar = OxmlElement("w:tcMar")
    for edge, width in (("top", "90"), ("bottom", "90"), ("left", "120"), ("right", "120")):
        node = OxmlElement(f"w:{edge}")
        node.set(qn("w:w"), width)
        node.set(qn("w:type"), "dxa")
        mar.append(node)
    tc_pr.append(mar)


doc = Document()
section = doc.sections[0]
section.page_width = Inches(8.5)
section.page_height = Inches(11)
section.top_margin = Inches(.7)
section.bottom_margin = Inches(.64)
section.left_margin = Inches(.85)
section.right_margin = Inches(.85)
section.header_distance = Inches(.35)
section.footer_distance = Inches(.35)

normal = doc.styles["Normal"]
normal.font.name = "Aptos"
normal.font.size = Pt(10)
normal.font.color.rgb = RGBColor(39, 39, 39)
normal.paragraph_format.space_after = Pt(6)
normal.paragraph_format.line_spacing = 1.1
for name, size, before, after in (("Title", 22, 0, 11), ("Heading 1", 13.5, 15, 6), ("Heading 2", 11.5, 11, 4)):
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
header.text = "SERENDIBBANK  /  SECURITY ASSESSMENT"
header.style = normal
header.runs[0].font.size = Pt(8)
header.runs[0].font.bold = True
header.runs[0].font.color.rgb = RGBColor(88, 65, 72)
footer = section.footer.paragraphs[0]
footer.text = "CyberRoot  •  Internal draft  •  19 November 2026"
footer.alignment = WD_ALIGN_PARAGRAPH.RIGHT
footer.runs[0].font.size = Pt(8)
footer.runs[0].font.color.rgb = RGBColor(110, 110, 110)

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
        table = doc.add_table(rows=len(block), cols=len(block[0]))
        table.autofit = False
        widths = [2.25, 4.55] if len(block[0]) == 2 else [1.35, 2.65, 2.8]
        for row_index, values in enumerate(block):
            row = table.rows[row_index]
            for column, value in enumerate(values):
                cell = row.cells[column]
                cell.width = Inches(widths[column])
                cell.vertical_alignment = WD_CELL_VERTICAL_ALIGNMENT.CENTER
                cell.text = value
                border(cell)
                padding(cell)
                if row_index == 0:
                    shade(cell, "38343A")
                elif row_index % 2 == 0:
                    shade(cell, "F6F4F4")
                for paragraph in cell.paragraphs:
                    paragraph.paragraph_format.space_after = Pt(0)
                    paragraph.paragraph_format.line_spacing = 1.08
                    if row_index == 0:
                        paragraph.paragraph_format.keep_with_next = True
                    for run in paragraph.runs:
                        run.font.size = Pt(8.8)
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
        if raw.startswith("## 8. Internal management addendum"):
            paragraph.paragraph_format.page_break_before = True
    elif raw.startswith("### "):
        doc.add_paragraph(raw[4:], style="Heading 2")
    elif re.match(r"^\d+\. ", raw):
        doc.add_paragraph(plain(re.sub(r"^\d+\. ", "", raw)), style="List Number")
    elif raw.startswith("- [ ] "):
        paragraph = doc.add_paragraph("☐  " + plain(raw[6:]))
        paragraph.paragraph_format.left_indent = Inches(.25)
        paragraph.paragraph_format.first_line_indent = Inches(-.14)
        if index + 1 < len(lines) and lines[index + 1].strip().startswith("- [ ] "):
            paragraph.paragraph_format.keep_with_next = True
    elif raw.startswith("- "):
        doc.add_paragraph(plain(raw[2:]), style="List Bullet")
    else:
        paragraph = doc.add_paragraph(plain(raw))
        if raw.startswith("**Version:**") or raw.startswith("**Owner:**") or raw.startswith("**Prepared:**") or raw.startswith("**Status:**"):
            paragraph.paragraph_format.space_after = Pt(2)
            if raw.startswith("**Status:**"):
                paragraph.paragraph_format.space_after = Pt(12)
        elif raw.startswith("**Management workspace:**"):
            paragraph.paragraph_format.space_before = Pt(5)
        elif raw.startswith("**Owner approval:**"):
            paragraph.paragraph_format.space_before = Pt(9)
        elif after_table:
            paragraph.paragraph_format.space_before = Pt(8)
    after_table = False
    index += 1

doc.core_properties.title = "SerendibBank Security Assessment Scope"
doc.core_properties.subject = "Authorized targets and rules of engagement"
doc.core_properties.author = "CyberRoot"
doc.save(OUTPUT)
print(OUTPUT)
