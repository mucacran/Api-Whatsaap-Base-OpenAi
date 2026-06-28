from pathlib import Path
from xml.sax.saxutils import escape


OUT = Path(__file__).with_name("uml-class-diagram.svg")


classes = [
    {
        "name": "Mucacran_Wa_Ai_Plugin",
        "x": 840,
        "y": 40,
        "w": 360,
        "attrs": [],
        "methods": ["+ init() : void"],
    },
    {
        "name": "Mucacran_Wa_Ai_Admin",
        "x": 70,
        "y": 260,
        "w": 520,
        "attrs": [],
        "methods": [
            "+ register_menu() : void",
            "+ enqueue_assets(hook : string) : void",
            "+ render_configuration_page() : void",
            "+ render_conversations_page() : void",
            "+ ajax_get_messages() : void",
            "+ ajax_send_message() : void",
            "- verify_ajax_request() : void",
            "- format_conversation(conversation : object) : array",
            "- format_message(message : object) : array",
        ],
    },
    {
        "name": "Mucacran_Wa_Ai_Webhook_Controller",
        "x": 710,
        "y": 250,
        "w": 650,
        "attrs": [],
        "methods": [
            "+ register_routes() : void",
            "+ verify_webhook(request : WP_REST_Request) : WP_REST_Response",
            "+ receive_webhook(request : WP_REST_Request) : WP_REST_Response",
            "- is_valid_signature(request : WP_REST_Request, raw_body : string) : bool",
            "- extract_messages(payload : array) : array",
            "- handle_incoming_message(message : array, payload : array) : void",
        ],
    },
    {
        "name": "Mucacran_Wa_Ai_DB",
        "x": 1490,
        "y": 220,
        "w": 620,
        "attrs": ["+ DB_VERSION_OPTION : string", "+ DB_VERSION : string"],
        "methods": [
            "+ activate() : void",
            "+ maybe_upgrade() : void",
            "+ conversations_table() : string",
            "+ messages_table() : string",
            "+ find_or_create_conversation(wa_id, phone, name) : int",
            "+ insert_message(message : array) : int",
            "- update_conversation_after_message(id, last_message, unread) : void",
            "+ get_conversations() : array",
            "+ get_conversation(conversation_id : int) : object|null",
            "+ get_messages(conversation_id : int) : array",
            "+ mark_read(conversation_id : int) : void",
        ],
    },
    {
        "name": "Mucacran_Wa_Ai_Config",
        "x": 1490,
        "y": 700,
        "w": 620,
        "attrs": ["+ OPTION_NAME : string"],
        "methods": [
            "+ all() : array",
            "+ get(key : string) : string",
            "+ save_from_admin(input : array) : void",
            "- sanitize_secret(input, key, current) : string",
            "+ mask_secret(value : string) : string",
        ],
    },
    {
        "name": "Mucacran_Wa_Ai_OpenAI_Service",
        "x": 710,
        "y": 760,
        "w": 430,
        "attrs": [],
        "methods": ["+ create_reply(message : string) : array"],
    },
    {
        "name": "Mucacran_Wa_Ai_WhatsApp_Service",
        "x": 70,
        "y": 760,
        "w": 470,
        "attrs": [],
        "methods": ["+ send_text_message(to : string, body : string) : array"],
    },
    {
        "name": "Mucacran_Wa_Ai_Hello_World_Controller",
        "x": 70,
        "y": 1120,
        "w": 540,
        "attrs": ["- page_slug : string", "- hook_suffix : string", "- submenu : string"],
        "methods": [
            "+ register_menu() : void",
            "+ enqueue_assets(hook_suffix : string) : void",
            "+ render() : void",
            "- get_menu_icon() : string",
        ],
    },
    {
        "name": "Mucacran_Wa_Ai_Hello_World_Model",
        "x": 700,
        "y": 1160,
        "w": 430,
        "attrs": [],
        "methods": ["+ get_title() : string", "+ get_message() : string"],
    },
    {
        "name": "saludoPersonalizado_Controller",
        "x": 1230,
        "y": 1120,
        "w": 430,
        "attrs": ["- page_slug : string"],
        "methods": [
            "+ __construct() : void",
            "+ register_menu() : void",
            "+ renderSaludoPersonalizado() : void",
        ],
    },
    {
        "name": "saludoPersonalizado_Model",
        "x": 1760,
        "y": 1145,
        "w": 360,
        "attrs": [],
        "methods": ["+ saludo(saludoP : mixed) : string"],
    },
    {
        "name": "Mucacran_Wa_Ai_Otra_Pagina_Model",
        "x": 1760,
        "y": 1365,
        "w": 360,
        "attrs": [],
        "methods": ["(empty placeholder)"],
    },
]

for c in classes:
    c["h"] = 96 + max(1, len(c["attrs"])) * 22 + len(c["methods"]) * 22

by_name = {c["name"]: c for c in classes}


def anchor(name, side):
    c = by_name[name]
    if side == "top":
        return c["x"] + c["w"] / 2, c["y"]
    if side == "bottom":
        return c["x"] + c["w"] / 2, c["y"] + c["h"]
    if side == "left":
        return c["x"], c["y"] + c["h"] / 2
    if side == "right":
        return c["x"] + c["w"], c["y"] + c["h"] / 2
    raise ValueError(side)


relationships = [
    ("Mucacran_Wa_Ai_Plugin", "bottom", "Mucacran_Wa_Ai_Admin", "top", "composition", "1"),
    ("Mucacran_Wa_Ai_Plugin", "bottom", "Mucacran_Wa_Ai_Webhook_Controller", "top", "composition", "1"),
    ("Mucacran_Wa_Ai_Plugin", "right", "Mucacran_Wa_Ai_DB", "top", "dependency", "1"),
    ("Mucacran_Wa_Ai_Admin", "right", "Mucacran_Wa_Ai_DB", "left", "association", "1"),
    ("Mucacran_Wa_Ai_Admin", "right", "Mucacran_Wa_Ai_Config", "left", "association", "1"),
    ("Mucacran_Wa_Ai_Admin", "bottom", "Mucacran_Wa_Ai_WhatsApp_Service", "top", "association", "0..1"),
    ("Mucacran_Wa_Ai_Webhook_Controller", "right", "Mucacran_Wa_Ai_DB", "left", "association", "1"),
    ("Mucacran_Wa_Ai_Webhook_Controller", "right", "Mucacran_Wa_Ai_Config", "left", "association", "1"),
    ("Mucacran_Wa_Ai_Webhook_Controller", "bottom", "Mucacran_Wa_Ai_OpenAI_Service", "top", "association", "0..1"),
    ("Mucacran_Wa_Ai_Webhook_Controller", "left", "Mucacran_Wa_Ai_WhatsApp_Service", "right", "association", "0..1"),
    ("Mucacran_Wa_Ai_OpenAI_Service", "right", "Mucacran_Wa_Ai_Config", "left", "association", "1"),
    ("Mucacran_Wa_Ai_WhatsApp_Service", "right", "Mucacran_Wa_Ai_Config", "left", "association", "1"),
    ("Mucacran_Wa_Ai_Hello_World_Controller", "right", "Mucacran_Wa_Ai_Hello_World_Model", "left", "association", "1"),
    ("saludoPersonalizado_Controller", "right", "saludoPersonalizado_Model", "left", "association", "1"),
]


def text(x, y, value, size=16, weight="400", family="Consolas, 'Courier New', monospace", fill="#111827"):
    return (
        f'<text x="{x:.1f}" y="{y:.1f}" font-family="{family}" font-size="{size}" '
        f'font-weight="{weight}" fill="{fill}">{escape(value)}</text>'
    )


def line_path(x1, y1, x2, y2, rel):
    stroke_dasharray = ' stroke-dasharray="8 6"' if rel == "dependency" else ""
    return (
        f'<line x1="{x1:.1f}" y1="{y1:.1f}" x2="{x2:.1f}" y2="{y2:.1f}" '
        f'stroke="#374151" stroke-width="2.2"{stroke_dasharray} marker-end="url(#arrow)" />'
    )


def diamond(x, y, x2, y2, filled):
    import math

    dx, dy = x2 - x, y2 - y
    length = math.hypot(dx, dy) or 1
    ux, uy = dx / length, dy / length
    px, py = -uy, ux
    size = 16
    points = [
        (x, y),
        (x + ux * size + px * size * 0.55, y + uy * size + py * size * 0.55),
        (x + ux * size * 2, y + uy * size * 2),
        (x + ux * size - px * size * 0.55, y + uy * size - py * size * 0.55),
    ]
    pts = " ".join(f"{px_:.1f},{py_:.1f}" for px_, py_ in points)
    fill = "#111827" if filled else "#FFFFFF"
    return f'<polygon points="{pts}" fill="{fill}" stroke="#374151" stroke-width="2.2" />'


svg = [
    '<svg xmlns="http://www.w3.org/2000/svg" width="2200" height="1640" viewBox="0 0 2200 1640">',
    "<defs>",
    '<marker id="arrow" markerWidth="10" markerHeight="10" refX="9" refY="3" orient="auto" markerUnits="strokeWidth">',
    '<path d="M0,0 L0,6 L9,3 z" fill="#374151" />',
    "</marker>",
    "</defs>",
    '<rect width="2200" height="1640" fill="#F8FAFC" />',
    text(70, 58, "UML Class Diagram - Api-Whatsaap-Base-OpenAi", 30, "700", "Arial, sans-serif"),
    text(70, 90, "Class (Clase), Attribute (Atributo), Method (Método), Visibility (Visibilidad), Multiplicity (Multiplicidad)", 16, "400", "Arial, sans-serif", "#475569"),
]

for source, sside, target, tside, rel, mult in relationships:
    x1, y1 = anchor(source, sside)
    x2, y2 = anchor(target, tside)
    if rel == "composition":
        svg.append(line_path(x1, y1, x2, y2, rel))
        svg.append(diamond(x1, y1, x2, y2, True))
    else:
        svg.append(line_path(x1, y1, x2, y2, rel))
    midx, midy = (x1 + x2) / 2, (y1 + y2) / 2
    svg.append(
        f'<rect x="{midx - 24:.1f}" y="{midy - 18:.1f}" width="48" height="22" rx="4" fill="#F8FAFC" />'
    )
    svg.append(text(midx - 16, midy - 2, mult, 14, "700", "Arial, sans-serif", "#0F172A"))

for c in classes:
    x, y, w, h = c["x"], c["y"], c["w"], c["h"]
    svg.append(f'<rect x="{x}" y="{y}" width="{w}" height="{h}" rx="6" fill="#FFFFFF" stroke="#1F2937" stroke-width="2" />')
    svg.append(f'<rect x="{x}" y="{y}" width="{w}" height="42" rx="6" fill="#E0F2FE" stroke="#1F2937" stroke-width="2" />')
    svg.append(f'<line x1="{x}" y1="{y + 42}" x2="{x + w}" y2="{y + 42}" stroke="#1F2937" stroke-width="2" />')
    attr_h = max(1, len(c["attrs"])) * 22 + 18
    svg.append(f'<line x1="{x}" y1="{y + 42 + attr_h}" x2="{x + w}" y2="{y + 42 + attr_h}" stroke="#1F2937" stroke-width="2" />')
    svg.append(text(x + 16, y + 27, c["name"], 17, "700"))
    yy = y + 66
    if c["attrs"]:
        for item in c["attrs"]:
            svg.append(text(x + 18, yy, item, 15))
            yy += 22
    else:
        svg.append(text(x + 18, yy, "(no attributes)", 15, "400", "Consolas, 'Courier New', monospace", "#64748B"))
        yy += 22
    yy = y + 42 + attr_h + 24
    for item in c["methods"]:
        svg.append(text(x + 18, yy, item, 15))
        yy += 22

legend_x, legend_y = 70, 1520
svg.append(f'<rect x="{legend_x}" y="{legend_y}" width="1120" height="74" rx="8" fill="#FFFFFF" stroke="#CBD5E1" />')
svg.append(text(legend_x + 20, legend_y + 28, "Legend:", 17, "700", "Arial, sans-serif"))
svg.append(f'<line x1="{legend_x + 105}" y1="{legend_y + 24}" x2="{legend_x + 205}" y2="{legend_y + 24}" stroke="#374151" stroke-width="2.2" marker-end="url(#arrow)" />')
svg.append(diamond(legend_x + 105, legend_y + 24, legend_x + 205, legend_y + 24, True))
svg.append(text(legend_x + 220, legend_y + 29, "Composition (Composición)", 16, "400", "Arial, sans-serif"))
svg.append(f'<line x1="{legend_x + 465}" y1="{legend_y + 24}" x2="{legend_x + 565}" y2="{legend_y + 24}" stroke="#374151" stroke-width="2.2" marker-end="url(#arrow)" />')
svg.append(text(legend_x + 580, legend_y + 29, "Association (Asociación)", 16, "400", "Arial, sans-serif"))
svg.append(f'<line x1="{legend_x + 805}" y1="{legend_y + 24}" x2="{legend_x + 905}" y2="{legend_y + 24}" stroke="#374151" stroke-width="2.2" stroke-dasharray="8 6" marker-end="url(#arrow)" />')
svg.append(text(legend_x + 920, legend_y + 29, "Dependency (Dependencia)", 16, "400", "Arial, sans-serif"))
svg.append(text(legend_x + 20, legend_y + 58, "No Inheritance (Herencia), Aggregation (Agregación) or Nested Class relationships were detected in authored classes.", 15, "400", "Arial, sans-serif", "#475569"))

svg.append("</svg>")
OUT.write_text("\n".join(svg), encoding="utf-8")
print(OUT)
