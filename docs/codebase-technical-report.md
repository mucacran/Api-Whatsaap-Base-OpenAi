# Reporte técnico del codebase para futuro UML

Proyecto: `Api-Whatsaap-Base-OpenAi`

Alcance: este reporte inspecciona el flujo real del plugin WordPress y separa Class (Clase) activa de Class (Clase) detectada pero inactiva/demo/placeholder. No contiene PlantUML, no contiene diagrama y no genera imagen.

## PART 1 - File Tree Summary

Archivos relevantes para clases, hooks, REST routes, servicios, persistencia, admin, modelos y configuración:

```text
api-whatsaap-base-openai.php
includes/
  class-plugin.php
  class-admin.php
  class-config.php
  class-db.php
  Controllers/
    controladores.php
    class-hello-world-controller.php
    saludoPersonalizadoControler.php
  Models/
    modelos.php
    class-hello-world-model.php
    class-saludo-personalizado-model.php
  Views/
    admin-hello-world.php
    saludoPersonalizado.php
controllers/
  class-webhook-controller.php
services/
  class-openai-service.php
  class-whatsapp-service.php
admin/
  settings-page.php
  conversations-page.php
assets/
  js/admin.js
```

Notas:

- `api-whatsaap-base-openai.php`, `includes/class-*.php`, `controllers/class-webhook-controller.php` y `services/class-*.php` forman el flujo activo.
- `admin/settings-page.php` y `admin/conversations-page.php` son vistas PHP incluidas por `Mucacran_Wa_Ai_Admin`; no definen clases.
- `assets/js/admin.js` consume los endpoints AJAX del admin; no define clases PHP.
- `includes/Controllers/*`, `includes/Models/*` e `includes/Views/*` existen, pero no son cargados desde el Bootstrap (Archivo de arranque) activo.

## PART 2 - Bootstrap / Entry Point Analysis

Main plugin file: `api-whatsaap-base-openai.php`.

Este archivo define constantes del plugin, carga las clases activas, registra el hook de activación y ejecuta `Mucacran_Wa_Ai_Plugin::init()`.

| File | What it loads | Hook or trigger | Active in runtime? | Notes |
|---|---|---|---|---|
| `api-whatsaap-base-openai.php` | `includes/class-config.php` | `require_once` directo | Yes | Carga `Mucacran_Wa_Ai_Config`. |
| `api-whatsaap-base-openai.php` | `includes/class-db.php` | `require_once` directo; `register_activation_hook(__FILE__, ['Mucacran_Wa_Ai_DB', 'activate'])` | Yes | Carga DB class (Clase de persistencia / base de datos) y registra creación/actualización de tablas en activación. |
| `api-whatsaap-base-openai.php` | `services/class-openai-service.php` | `require_once` directo | Yes | Carga el Service (Servicio) de OpenAI; se instancia bajo demanda desde webhook. |
| `api-whatsaap-base-openai.php` | `services/class-whatsapp-service.php` | `require_once` directo | Yes | Carga el Service (Servicio) de WhatsApp; se instancia desde webhook y AJAX admin. |
| `api-whatsaap-base-openai.php` | `controllers/class-webhook-controller.php` | `require_once` directo | Yes | Carga Controller (Controlador) REST/webhook. |
| `api-whatsaap-base-openai.php` | `includes/class-admin.php` | `require_once` directo | Yes | Carga Controller (Controlador) admin. |
| `api-whatsaap-base-openai.php` | `includes/class-plugin.php` | `require_once` directo; `Mucacran_Wa_Ai_Plugin::init()` | Yes | Inicia el Runtime flow (Flujo de ejecución). |
| `includes/class-plugin.php` | Instancia `Mucacran_Wa_Ai_Admin` | `add_action('admin_menu', ...)`, `add_action('admin_enqueue_scripts', ...)`, `wp_ajax_*` | Yes | Admin runtime activo. |
| `includes/class-plugin.php` | Instancia `Mucacran_Wa_Ai_Webhook_Controller` | `add_action('rest_api_init', ...)` | Yes | REST/webhook runtime activo. |
| `includes/class-plugin.php` | Usa `Mucacran_Wa_Ai_DB::maybe_upgrade()` | Llamada estática en `init()` | Yes | Verifica versión de tablas cuando el plugin ya está activo. |
| `includes/Controllers/controladores.php` | `class-hello-world-controller.php`, `saludoPersonalizadoControler.php` | No hay `require_once` desde bootstrap activo | No | Detected but not part of active runtime flow. |
| `includes/Models/modelos.php` | Modelos Hello World y saludo | No hay `require_once` desde bootstrap activo | No | Detected but not part of active runtime flow. |

Clases cargadas directamente por el Bootstrap (Archivo de arranque):

- `Mucacran_Wa_Ai_Config`
- `Mucacran_Wa_Ai_DB`
- `Mucacran_Wa_Ai_OpenAI_Service`
- `Mucacran_Wa_Ai_WhatsApp_Service`
- `Mucacran_Wa_Ai_Webhook_Controller`
- `Mucacran_Wa_Ai_Admin`
- `Mucacran_Wa_Ai_Plugin`

Hooks registrados:

- `register_activation_hook(__FILE__, ['Mucacran_Wa_Ai_DB', 'activate'])`
- `admin_menu` -> `Mucacran_Wa_Ai_Admin::register_menu()`
- `admin_enqueue_scripts` -> `Mucacran_Wa_Ai_Admin::enqueue_assets()`
- `wp_ajax_mucacran_wa_ai_get_messages` -> `Mucacran_Wa_Ai_Admin::ajax_get_messages()`
- `wp_ajax_mucacran_wa_ai_send_message` -> `Mucacran_Wa_Ai_Admin::ajax_send_message()`
- `rest_api_init` -> `Mucacran_Wa_Ai_Webhook_Controller::register_routes()`

## PART 3 - Active Class Inventory

| Class (Clase) | File path | Layer / Module | Responsibility (Responsabilidad) | Key attributes (Atributos clave) | Key methods (Métodos clave) | Active runtime role |
|---|---|---|---|---|---|---|
| `Mucacran_Wa_Ai_Plugin` | `includes/class-plugin.php` | Bootstrap / Core | Inicializa objetos runtime y registra hooks principales. | Ninguno | `init()` | Punto central de arranque después del main plugin file. |
| `Mucacran_Wa_Ai_Admin` | `includes/class-admin.php` | Admin | Registra menú admin, carga assets, renderiza páginas, atiende AJAX de conversaciones y envío manual. | Ninguno | `register_menu()`, `enqueue_assets()`, `render_configuration_page()`, `render_conversations_page()`, `ajax_get_messages()`, `ajax_send_message()` | Maneja toda la UI administrativa activa. |
| `Mucacran_Wa_Ai_Config` | `includes/class-config.php` | Config / Settings | Lee, normaliza, guarda y enmascara opciones del plugin. | `OPTION_NAME` | `all()`, `get()`, `save_from_admin()`, `mask_secret()` | Fuente de configuración para admin, webhook y servicios. |
| `Mucacran_Wa_Ai_DB` | `includes/class-db.php` | Database / Persistence | Crea tablas y persiste conversaciones/mensajes. | `DB_VERSION_OPTION`, `DB_VERSION` | `activate()`, `maybe_upgrade()`, `find_or_create_conversation()`, `insert_message()`, `get_conversations()`, `get_conversation()`, `get_messages()`, `mark_read()` | Repository / DB class (Clase de persistencia / base de datos) principal. |
| `Mucacran_Wa_Ai_Webhook_Controller` | `controllers/class-webhook-controller.php` | REST / Webhook | Registra ruta REST, verifica webhook, recibe mensajes, orquesta DB/OpenAI/WhatsApp. | Ninguno | `register_routes()`, `verify_webhook()`, `receive_webhook()`, `is_valid_signature()`, `extract_messages()`, `handle_incoming_message()` | Controller (Controlador) del flujo entrante de WhatsApp. |
| `Mucacran_Wa_Ai_OpenAI_Service` | `services/class-openai-service.php` | Service | Llama a OpenAI Chat Completions para generar respuesta. | Ninguno | `create_reply()` | Servicio externo usado bajo demanda desde webhook. |
| `Mucacran_Wa_Ai_WhatsApp_Service` | `services/class-whatsapp-service.php` | Service | Envía mensajes de texto usando WhatsApp Cloud API. | Ninguno | `send_text_message()` | Servicio externo usado por webhook y envío manual admin. |

## PART 4 - Detected but inactive / demo / placeholder classes

| Class (Clase) | File path | Why it appears inactive or secondary | Should it be included in the final UML diagram? Yes/No | Reason |
|---|---|---|---|---|
| `Mucacran_Wa_Ai_Hello_World_Controller` | `includes/Controllers/class-hello-world-controller.php` | Su loader `includes/Controllers/controladores.php` no es requerido por `api-whatsaap-base-openai.php`; no hay instancia desde el runtime activo. | No | Detected but not part of active runtime flow. Parece demo MVC/admin. |
| `Mucacran_Wa_Ai_Hello_World_Model` | `includes/Models/class-hello-world-model.php` | Su loader `includes/Models/modelos.php` no es requerido por el bootstrap activo; solo es usado por el controlador Hello World inactivo. | No | Modelo demo asociado a controlador inactivo. |
| `Mucacran_Wa_Ai_Otra_Pagina_Model` | `includes/Models/class-hello-world-model.php` | Clase vacía sin métodos ni referencias desde flujo activo. | No | Placeholder. |
| `saludoPersonalizado_Controller` | `includes/Controllers/saludoPersonalizadoControler.php` | Su loader no es requerido por el bootstrap activo; no hay instancia desde `Mucacran_Wa_Ai_Plugin`. | No | Detected but not part of active runtime flow. Parece demo/ejercicio MVC. |
| `saludoPersonalizado_Model` | `includes/Models/class-saludo-personalizado-model.php` | Solo es usado por `saludoPersonalizado_Controller`, que está inactivo. | No | Modelo secundario no conectado al runtime activo. |

## PART 5 - Method-Level Responsibility Summary

Class: `Mucacran_Wa_Ai_Plugin`

- `init()`
  - Purpose: crear objetos runtime principales, ejecutar upgrade de DB y registrar hooks WordPress.
  - Calls other plugin classes? Yes: crea `Mucacran_Wa_Ai_Admin`, crea `Mucacran_Wa_Ai_Webhook_Controller`, llama `Mucacran_Wa_Ai_DB::maybe_upgrade()`.
  - Uses WordPress functions? Yes: `add_action()`.
  - Uses external APIs? No.
  - Reads/writes database? Indirectamente por `Mucacran_Wa_Ai_DB::maybe_upgrade()`.
  - Should appear in UML? Yes.

Class: `Mucacran_Wa_Ai_Admin`

- `register_menu()`
  - Purpose: registrar página principal y subpáginas admin.
  - Calls other plugin classes? No.
  - Uses WordPress functions? Yes: `add_menu_page()`, `add_submenu_page()`, `__()`.
  - Uses external APIs? No.
  - Reads/writes database? No.
  - Should appear in UML? Yes.

- `enqueue_assets($hook)`
  - Purpose: cargar CSS/JS admin solo en páginas del plugin y publicar datos AJAX al JS.
  - Calls other plugin classes? No.
  - Uses WordPress functions? Yes: `wp_enqueue_style()`, `wp_enqueue_script()`, `wp_localize_script()`, `admin_url()`, `wp_create_nonce()`.
  - Uses external APIs? No.
  - Reads/writes database? No.
  - Should appear in UML? Yes.

- `render_configuration_page()`
  - Purpose: validar permiso, guardar configuración enviada y renderizar `admin/settings-page.php`.
  - Calls other plugin classes? Yes: `Mucacran_Wa_Ai_Config::save_from_admin()`, `Mucacran_Wa_Ai_Config::all()`.
  - Uses WordPress functions? Yes: `current_user_can()`, `wp_die()`, `wp_verify_nonce()`, sanitización, `add_settings_error()`.
  - Uses external APIs? No.
  - Reads/writes database? Indirectamente mediante WordPress Options API dentro de `Mucacran_Wa_Ai_Config`.
  - Should appear in UML? Yes.

- `render_conversations_page()`
  - Purpose: consultar conversaciones/mensajes y renderizar `admin/conversations-page.php`.
  - Calls other plugin classes? Yes: `Mucacran_Wa_Ai_DB::get_conversations()`, `get_conversation()`, `get_messages()`, `mark_read()`.
  - Uses WordPress functions? Yes: `current_user_can()`, `wp_die()`, `absint()`.
  - Uses external APIs? No.
  - Reads/writes database? Yes, vía `Mucacran_Wa_Ai_DB`.
  - Should appear in UML? Yes.

- `ajax_get_messages()`
  - Purpose: devolver mensajes formateados para una conversación seleccionada.
  - Calls other plugin classes? Yes: `Mucacran_Wa_Ai_DB::get_conversation()`, `mark_read()`, `get_messages()`.
  - Uses WordPress functions? Yes: `wp_send_json_error()`, `wp_send_json_success()`, nonce/capability vía `verify_ajax_request()`.
  - Uses external APIs? No.
  - Reads/writes database? Yes, lee mensajes y marca conversación como leída.
  - Should appear in UML? Yes.

- `ajax_send_message()`
  - Purpose: enviar respuesta manual admin por WhatsApp y registrar el mensaje saliente.
  - Calls other plugin classes? Yes: `Mucacran_Wa_Ai_DB`, `Mucacran_Wa_Ai_WhatsApp_Service`.
  - Uses WordPress functions? Yes: `wp_send_json_error()`, `wp_send_json_success()`, sanitización.
  - Uses external APIs? Indirectamente WhatsApp Cloud API por `Mucacran_Wa_Ai_WhatsApp_Service`.
  - Reads/writes database? Yes, lee conversación e inserta mensaje saliente.
  - Should appear in UML? Yes.

- `verify_ajax_request()`
  - Purpose: centralizar verificación de permisos y nonce para AJAX admin.
  - Calls other plugin classes? No.
  - Uses WordPress functions? Yes: `current_user_can()`, `wp_send_json_error()`, `check_ajax_referer()`.
  - Uses external APIs? No.
  - Reads/writes database? No.
  - Should appear in UML? Yes, como método privado.

- `format_conversation($conversation)`
  - Purpose: convertir fila de conversación a array simple para JavaScript.
  - Calls other plugin classes? No.
  - Uses WordPress functions? No.
  - Uses external APIs? No.
  - Reads/writes database? No; recibe objeto ya consultado.
  - Should appear in UML? Yes, como método privado.

- `format_message($message)`
  - Purpose: convertir fila de mensaje a array simple para JavaScript.
  - Calls other plugin classes? No.
  - Uses WordPress functions? Yes: `mysql2date()`.
  - Uses external APIs? No.
  - Reads/writes database? No; recibe objeto ya consultado.
  - Should appear in UML? Yes, como método privado.

Class: `Mucacran_Wa_Ai_Config`

- `all()`
  - Purpose: devolver configuración guardada con defaults.
  - Calls other plugin classes? No.
  - Uses WordPress functions? Yes: `get_option()`, `wp_parse_args()`.
  - Uses external APIs? No.
  - Reads/writes database? Indirectamente lee Options API.
  - Should appear in UML? Yes.

- `get($key)`
  - Purpose: devolver un valor de configuración por clave.
  - Calls other plugin classes? No; llama `self::all()`.
  - Uses WordPress functions? Indirectamente por `all()`.
  - Uses external APIs? No.
  - Reads/writes database? Indirectamente lee Options API.
  - Should appear in UML? Yes.

- `save_from_admin($input)`
  - Purpose: sanitizar y guardar configuración enviada desde admin.
  - Calls other plugin classes? No; usa `self::all()` y `self::sanitize_secret()`.
  - Uses WordPress functions? Yes: sanitización, `wp_unslash()`, `update_option()`.
  - Uses external APIs? No.
  - Reads/writes database? Indirectamente escribe Options API.
  - Should appear in UML? Yes.

- `sanitize_secret($input, $key, $current)`
  - Purpose: conservar secretos previos cuando el campo admin llega vacío.
  - Calls other plugin classes? No.
  - Uses WordPress functions? Yes: `sanitize_text_field()`, `wp_unslash()`.
  - Uses external APIs? No.
  - Reads/writes database? No.
  - Should appear in UML? Yes, como método privado.

- `mask_secret($value)`
  - Purpose: mostrar una versión enmascarada de valores sensibles.
  - Calls other plugin classes? No.
  - Uses WordPress functions? No.
  - Uses external APIs? No.
  - Reads/writes database? No.
  - Should appear in UML? Yes.

Class: `Mucacran_Wa_Ai_DB`

- `activate()`
  - Purpose: crear o actualizar tablas custom del plugin.
  - Calls other plugin classes? No; usa `self::conversations_table()` y `self::messages_table()`.
  - Uses WordPress functions? Yes: `dbDelta()`, `update_option()`, global `$wpdb`.
  - Uses external APIs? No.
  - Reads/writes database? Yes, crea/actualiza tablas y opción de versión.
  - Should appear in UML? Yes.

- `maybe_upgrade()`
  - Purpose: ejecutar `activate()` si la versión de DB guardada no coincide.
  - Calls other plugin classes? No; usa `self::activate()`.
  - Uses WordPress functions? Yes: `get_option()`.
  - Uses external APIs? No.
  - Reads/writes database? Indirectamente puede crear/actualizar tablas.
  - Should appear in UML? Yes.

- `conversations_table()`
  - Purpose: devolver nombre completo de tabla de conversaciones con prefijo WordPress.
  - Calls other plugin classes? No.
  - Uses WordPress functions? Usa global `$wpdb`.
  - Uses external APIs? No.
  - Reads/writes database? No.
  - Should appear in UML? Yes.

- `messages_table()`
  - Purpose: devolver nombre completo de tabla de mensajes con prefijo WordPress.
  - Calls other plugin classes? No.
  - Uses WordPress functions? Usa global `$wpdb`.
  - Uses external APIs? No.
  - Reads/writes database? No.
  - Should appear in UML? Yes.

- `find_or_create_conversation($wa_id, $phone, $name = '')`
  - Purpose: buscar conversación por WhatsApp ID o crearla.
  - Calls other plugin classes? No.
  - Uses WordPress functions? Yes: `current_time()`, global `$wpdb`.
  - Uses external APIs? No.
  - Reads/writes database? Yes, consulta/inserta/actualiza conversaciones.
  - Should appear in UML? Yes.

- `insert_message($message)`
  - Purpose: insertar mensaje y actualizar resumen de conversación.
  - Calls other plugin classes? No; llama `self::update_conversation_after_message()`.
  - Uses WordPress functions? Yes: sanitización, `current_time()`, `wp_json_encode()`, global `$wpdb`.
  - Uses external APIs? No.
  - Reads/writes database? Yes, inserta en mensajes y actualiza conversaciones.
  - Should appear in UML? Yes.

- `update_conversation_after_message($conversation_id, $last_message, $is_unread)`
  - Purpose: actualizar preview, timestamps y contador unread.
  - Calls other plugin classes? No.
  - Uses WordPress functions? Yes: `current_time()`, `$wpdb->prepare()`.
  - Uses external APIs? No.
  - Reads/writes database? Yes, actualiza conversación.
  - Should appear in UML? Yes, como método privado.

- `get_conversations()`
  - Purpose: obtener lista reciente de conversaciones para admin.
  - Calls other plugin classes? No.
  - Uses WordPress functions? Usa global `$wpdb`.
  - Uses external APIs? No.
  - Reads/writes database? Yes, lee conversaciones.
  - Should appear in UML? Yes.

- `get_conversation($conversation_id)`
  - Purpose: obtener una conversación por ID.
  - Calls other plugin classes? No.
  - Uses WordPress functions? Yes: `absint()`, `$wpdb->prepare()`.
  - Uses external APIs? No.
  - Reads/writes database? Yes, lee conversación.
  - Should appear in UML? Yes.

- `get_messages($conversation_id)`
  - Purpose: obtener mensajes de una conversación.
  - Calls other plugin classes? No.
  - Uses WordPress functions? Yes: `absint()`, `$wpdb->prepare()`.
  - Uses external APIs? No.
  - Reads/writes database? Yes, lee mensajes.
  - Should appear in UML? Yes.

- `mark_read($conversation_id)`
  - Purpose: poner `unread_admin` en cero.
  - Calls other plugin classes? No.
  - Uses WordPress functions? Yes: `absint()`, global `$wpdb`.
  - Uses external APIs? No.
  - Reads/writes database? Yes, actualiza conversación.
  - Should appear in UML? Yes.

Class: `Mucacran_Wa_Ai_Webhook_Controller`

- `register_routes()`
  - Purpose: registrar endpoint REST `mucacran-wa-ai/v1/webhook`.
  - Calls other plugin classes? No.
  - Uses WordPress functions? Yes: `register_rest_route()`, `WP_REST_Server`.
  - Uses external APIs? No.
  - Reads/writes database? No.
  - Should appear in UML? Yes.

- `verify_webhook(WP_REST_Request $request)`
  - Purpose: validar token de verificación enviado por Meta y devolver challenge.
  - Calls other plugin classes? Yes: `Mucacran_Wa_Ai_Config::get('webhook_verify_token')`.
  - Uses WordPress functions? Yes: `sanitize_text_field()`, `WP_REST_Response`.
  - Uses external APIs? No directo; responde al handshake de Meta.
  - Reads/writes database? Indirectamente lee Options API por Config.
  - Should appear in UML? Yes.

- `receive_webhook(WP_REST_Request $request)`
  - Purpose: recibir payload JSON, validar firma, extraer mensajes y delegar manejo.
  - Calls other plugin classes? Indirectamente mediante `handle_incoming_message()`.
  - Uses WordPress functions? Yes: `WP_REST_Response`.
  - Uses external APIs? Recibe webhook de WhatsApp/Meta.
  - Reads/writes database? Indirectamente por `handle_incoming_message()`.
  - Should appear in UML? Yes.

- `is_valid_signature(WP_REST_Request $request, $raw_body)`
  - Purpose: validar firma HMAC cuando `meta_app_secret` está configurado.
  - Calls other plugin classes? Yes: `Mucacran_Wa_Ai_Config::get('meta_app_secret')`.
  - Uses WordPress functions? Usa headers de `WP_REST_Request`.
  - Uses external APIs? No.
  - Reads/writes database? Indirectamente lee Options API por Config.
  - Should appear in UML? Yes, como método privado.

- `extract_messages($payload)`
  - Purpose: normalizar mensajes de texto soportados desde el payload webhook.
  - Calls other plugin classes? No.
  - Uses WordPress functions? Yes: `sanitize_text_field()`, `sanitize_textarea_field()`.
  - Uses external APIs? No.
  - Reads/writes database? No.
  - Should appear in UML? Yes, como método privado.

- `handle_incoming_message($message, $payload)`
  - Purpose: guardar mensaje entrante, crear respuesta OpenAI, enviarla por WhatsApp y guardar salida.
  - Calls other plugin classes? Yes: `Mucacran_Wa_Ai_DB`, `Mucacran_Wa_Ai_OpenAI_Service`, `Mucacran_Wa_Ai_WhatsApp_Service`.
  - Uses WordPress functions? No relevante más allá de estructuras PHP.
  - Uses external APIs? Indirectamente OpenAI y WhatsApp.
  - Reads/writes database? Yes, crea/actualiza conversación e inserta mensajes.
  - Should appear in UML? Yes, como método privado.

Class: `Mucacran_Wa_Ai_OpenAI_Service`

- `create_reply($message)`
  - Purpose: llamar a OpenAI y devolver texto de respuesta o error.
  - Calls other plugin classes? Yes: `Mucacran_Wa_Ai_Config::get()`.
  - Uses WordPress functions? Yes: `wp_remote_post()`, `wp_json_encode()`, `is_wp_error()`, `wp_remote_retrieve_response_code()`, `wp_remote_retrieve_body()`, `__()`.
  - Uses external APIs? Yes: OpenAI API endpoint `https://api.openai.com/v1/chat/completions`.
  - Reads/writes database? Indirectamente lee Options API por Config.
  - Should appear in UML? Yes.

Class: `Mucacran_Wa_Ai_WhatsApp_Service`

- `send_text_message($to, $body)`
  - Purpose: enviar mensaje texto por WhatsApp Cloud API y devolver estado/ID.
  - Calls other plugin classes? Yes: `Mucacran_Wa_Ai_Config::get()`.
  - Uses WordPress functions? Yes: `wp_remote_post()`, `wp_json_encode()`, `is_wp_error()`, response helpers, `__()`.
  - Uses external APIs? Yes: Meta Graph API `https://graph.facebook.com/{version}/{phone_number_id}/messages`.
  - Reads/writes database? Indirectamente lee Options API por Config.
  - Should appear in UML? Yes.

## PART 6 - Object Creation and Dependency Evidence

| Source class | Target class | Evidence from code | Is target stored as property? Yes/No | Is target created with new? Yes/No | Is target used statically? Yes/No | Is target only temporary? Yes/No |
|---|---|---|---|---|---|---|
| `Mucacran_Wa_Ai_Plugin` | `Mucacran_Wa_Ai_Admin` | `new Mucacran_Wa_Ai_Admin()` inside `init()`; object used in `add_action()` callbacks | No | Yes | No | No; WordPress hook callback retains the object reference. |
| `Mucacran_Wa_Ai_Plugin` | `Mucacran_Wa_Ai_Webhook_Controller` | `new Mucacran_Wa_Ai_Webhook_Controller()` inside `init()`; object used in `rest_api_init` callback | No | Yes | No | No; WordPress hook callback retains the object reference. |
| `Mucacran_Wa_Ai_Plugin` | `Mucacran_Wa_Ai_DB` | `Mucacran_Wa_Ai_DB::maybe_upgrade()` static call | No | No | Yes | Yes |
| `Mucacran_Wa_Ai_Admin` | `Mucacran_Wa_Ai_Config` | `Mucacran_Wa_Ai_Config::save_from_admin()`, `::all()` | No | No | Yes | Yes |
| `Mucacran_Wa_Ai_Admin` | `Mucacran_Wa_Ai_DB` | `Mucacran_Wa_Ai_DB::get_conversations()`, `::get_conversation()`, `::get_messages()`, `::mark_read()`, `::insert_message()` | No | No | Yes | Yes |
| `Mucacran_Wa_Ai_Admin` | `Mucacran_Wa_Ai_WhatsApp_Service` | `new Mucacran_Wa_Ai_WhatsApp_Service()` inside `ajax_send_message()` | No | Yes | No | Yes |
| `Mucacran_Wa_Ai_Webhook_Controller` | `Mucacran_Wa_Ai_Config` | `Mucacran_Wa_Ai_Config::get('webhook_verify_token')`, `::get('meta_app_secret')` | No | No | Yes | Yes |
| `Mucacran_Wa_Ai_Webhook_Controller` | `Mucacran_Wa_Ai_DB` | `Mucacran_Wa_Ai_DB::find_or_create_conversation()`, `::insert_message()` | No | No | Yes | Yes |
| `Mucacran_Wa_Ai_Webhook_Controller` | `Mucacran_Wa_Ai_OpenAI_Service` | `new Mucacran_Wa_Ai_OpenAI_Service()` inside `handle_incoming_message()` | No | Yes | No | Yes |
| `Mucacran_Wa_Ai_Webhook_Controller` | `Mucacran_Wa_Ai_WhatsApp_Service` | `new Mucacran_Wa_Ai_WhatsApp_Service()` inside `handle_incoming_message()` | No | Yes | No | Yes |
| `Mucacran_Wa_Ai_OpenAI_Service` | `Mucacran_Wa_Ai_Config` | `Mucacran_Wa_Ai_Config::get('openai_api_key')`, `::get('openai_model')`, `::get('system_prompt')` | No | No | Yes | Yes |
| `Mucacran_Wa_Ai_WhatsApp_Service` | `Mucacran_Wa_Ai_Config` | `Mucacran_Wa_Ai_Config::get('whatsapp_access_token')`, `::get('whatsapp_phone_number_id')` | No | No | Yes | Yes |

No active class stores another plugin class as an Attribute (Atributo). There is no Aggregation (Agregación) evidence in the active runtime.

## PART 7 - Relationship Candidates for UML

| Source class | Target class | Suggested relationship | Multiplicity | Confidence High/Medium/Low | Reason |
|---|---|---|---|---|---|
| `Mucacran_Wa_Ai_Plugin` | `Mucacran_Wa_Ai_Admin` | Composition (Composición) | `1` | Medium | `Plugin::init()` creates the admin object for plugin lifecycle and registers it in hooks, but does not store it as a property. If strict property ownership is required, use Association (Asociación). |
| `Mucacran_Wa_Ai_Plugin` | `Mucacran_Wa_Ai_Webhook_Controller` | Composition (Composición) | `1` | Medium | `Plugin::init()` creates the webhook controller for plugin lifecycle and registers it in hooks, but does not store it as a property. If strict ownership is required, use Association (Asociación). |
| `Mucacran_Wa_Ai_Plugin` | `Mucacran_Wa_Ai_DB` | Dependency (Dependencia) | `1` | High | Static call `Mucacran_Wa_Ai_DB::maybe_upgrade()`. |
| `Mucacran_Wa_Ai_Admin` | `Mucacran_Wa_Ai_Config` | Dependency (Dependencia) | `1` | High | Static calls to config methods; no instance stored or created. |
| `Mucacran_Wa_Ai_Admin` | `Mucacran_Wa_Ai_DB` | Dependency (Dependencia) | `1` | High | Static DB/repository calls; no instance stored or created. |
| `Mucacran_Wa_Ai_Admin` | `Mucacran_Wa_Ai_WhatsApp_Service` | Association (Asociación) | `0..1` per AJAX request | High | Temporary object created with `new` inside `ajax_send_message()` and used through `send_text_message()`. |
| `Mucacran_Wa_Ai_Webhook_Controller` | `Mucacran_Wa_Ai_Config` | Dependency (Dependencia) | `1` | High | Static config calls for token and app secret. |
| `Mucacran_Wa_Ai_Webhook_Controller` | `Mucacran_Wa_Ai_DB` | Dependency (Dependencia) | `1` | High | Static repository calls for conversation/message persistence. |
| `Mucacran_Wa_Ai_Webhook_Controller` | `Mucacran_Wa_Ai_OpenAI_Service` | Association (Asociación) | `0..1` per incoming message | High | Temporary object created with `new` and method call `create_reply()`. |
| `Mucacran_Wa_Ai_Webhook_Controller` | `Mucacran_Wa_Ai_WhatsApp_Service` | Association (Asociación) | `0..1` per incoming message | High | Temporary object created with `new` and method call `send_text_message()`. |
| `Mucacran_Wa_Ai_OpenAI_Service` | `Mucacran_Wa_Ai_Config` | Dependency (Dependencia) | `1` | High | Static config calls for API key/model/system prompt. |
| `Mucacran_Wa_Ai_WhatsApp_Service` | `Mucacran_Wa_Ai_Config` | Dependency (Dependencia) | `1` | High | Static config calls for token and phone number ID. |

Inheritance (Herencia): no `extends` detected in active classes.

Aggregation (Agregación): no active class stores a reference to another object without lifecycle ownership.

Nested Class (Clase anidada): no class is defined inside another class.

## PART 8 - External Dependencies

| External dependency | Used by class | Purpose | Should appear in final UML? Yes/No/As note only |
|---|---|---|---|
| WordPress plugin bootstrap constants/functions | `api-whatsaap-base-openai.php` | Define path, URL, version, text domain, Graph API version. | As note only |
| `register_activation_hook()` | `api-whatsaap-base-openai.php` / `Mucacran_Wa_Ai_DB` | Ejecutar creación/upgrade de tablas al activar plugin. | As note only |
| WordPress hooks `add_action()` | `Mucacran_Wa_Ai_Plugin` | Conectar admin, AJAX y REST init al runtime. | As note only |
| Admin Menu API | `Mucacran_Wa_Ai_Admin` | Crear menú y submenús admin. | As note only |
| Admin enqueue/localization API | `Mucacran_Wa_Ai_Admin` | Cargar `assets/css/admin.css`, `assets/js/admin.js`, `MucacranWaAi`. | As note only |
| WordPress AJAX actions | `Mucacran_Wa_Ai_Admin`, `assets/js/admin.js` | Obtener mensajes y enviar respuesta manual. | As note only |
| WordPress REST API | `Mucacran_Wa_Ai_Webhook_Controller` | Registrar y responder endpoint webhook. | As note only |
| `WP_REST_Request` | `Mucacran_Wa_Ai_Webhook_Controller` | Tipo de parámetro para requests REST. | As note only / parameter type |
| `WP_REST_Response` | `Mucacran_Wa_Ai_Webhook_Controller` | Tipo de retorno para respuestas REST. | As note only / return type |
| WordPress Options API | `Mucacran_Wa_Ai_Config`, `Mucacran_Wa_Ai_DB` | Guardar configuración y versión de DB. | As note only |
| `$wpdb` | `Mucacran_Wa_Ai_DB` | Consultas, inserts, updates y nombres de tablas. | As note only |
| `dbDelta()` | `Mucacran_Wa_Ai_DB` | Crear/actualizar tablas custom. | As note only |
| WordPress HTTP API `wp_remote_post()` | `Mucacran_Wa_Ai_OpenAI_Service`, `Mucacran_Wa_Ai_WhatsApp_Service` | Llamadas HTTP externas. | As note only |
| OpenAI API | `Mucacran_Wa_Ai_OpenAI_Service` | Generar respuesta de asistente. | As note only |
| WhatsApp Cloud API / Meta Graph API | `Mucacran_Wa_Ai_WhatsApp_Service`, webhook payload recibido por `Mucacran_Wa_Ai_Webhook_Controller` | Recibir y enviar mensajes WhatsApp. | As note only |
| WordPress nonce/capability functions | `Mucacran_Wa_Ai_Admin`, `Mucacran_Wa_Ai_Webhook_Controller`, views admin | Seguridad de admin, formularios y AJAX. | As note only |
| WordPress i18n functions | Varias clases/vistas | Textos traducibles. | No |

## PART 9 - Database and Data Structures

| Table or data structure | Created/used by | Main fields if visible | Purpose | Related class |
|---|---|---|---|---|
| `{$wpdb->prefix}mucacran_wa_ai_conversations` | Created by `Mucacran_Wa_Ai_DB::activate()`; used by DB methods and admin/webhook indirectly | `id`, `wa_id`, `contact_phone`, `contact_name`, `last_message`, `last_message_at`, `unread_admin`, `created_at`, `updated_at` | Representar una conversación por contacto WhatsApp. | `Mucacran_Wa_Ai_DB` |
| `{$wpdb->prefix}mucacran_wa_ai_messages` | Created by `Mucacran_Wa_Ai_DB::activate()`; used by `insert_message()` and `get_messages()` | `id`, `conversation_id`, `direction`, `sender_type`, `message_type`, `message_body`, `whatsapp_message_id`, `raw_payload`, `delivery_status`, `error_message`, `created_at` | Guardar mensajes entrantes/salientes de usuario, admin, AI o sistema. | `Mucacran_Wa_Ai_DB` |
| WordPress option `mucacran_wa_ai_settings` | Read/written by `Mucacran_Wa_Ai_Config` | `openai_api_key`, `openai_model`, `system_prompt`, `whatsapp_access_token`, `whatsapp_phone_number_id`, `whatsapp_business_account_id`, `webhook_verify_token`, `meta_app_secret` | Configuración de API y webhook. | `Mucacran_Wa_Ai_Config` |
| WordPress option `mucacran_wa_ai_db_version` | Read/written by `Mucacran_Wa_Ai_DB` | Version string `1` | Control de migración/upgrade de tablas. | `Mucacran_Wa_Ai_DB` |
| Webhook extracted message array | Built by `Mucacran_Wa_Ai_Webhook_Controller::extract_messages()` | `wa_id`, `contact_phone`, `contact_name`, `message_body`, `whatsapp_message_id` | Normalizar payload de WhatsApp antes de persistir y responder. | `Mucacran_Wa_Ai_Webhook_Controller` |
| Message insert array | Passed to `Mucacran_Wa_Ai_DB::insert_message()` | `conversation_id`, `direction`, `sender_type`, `message_type`, `message_body`, `whatsapp_message_id`, `raw_payload`, `delivery_status`, `error_message` | DTO informal para persistir mensajes. | `Mucacran_Wa_Ai_DB` |
| OpenAI service result array | Returned by `Mucacran_Wa_Ai_OpenAI_Service::create_reply()` | `success`, `reply`, `error`, `data` | Comunicar éxito/error y respuesta generada. | `Mucacran_Wa_Ai_OpenAI_Service` |
| WhatsApp service result array | Returned by `Mucacran_Wa_Ai_WhatsApp_Service::send_text_message()` | `success`, `whatsapp_message_id`, `error`, `data` | Comunicar envío y message ID de WhatsApp. | `Mucacran_Wa_Ai_WhatsApp_Service` |
| JavaScript object `MucacranWaAi` | Created by `wp_localize_script()` in `Mucacran_Wa_Ai_Admin::enqueue_assets()` | `ajaxUrl`, `nonce`, `i18n.sending`, `i18n.send`, `i18n.error` | Configurar AJAX admin en `assets/js/admin.js`. | `Mucacran_Wa_Ai_Admin` |

## PART 10 - Recommended Final UML Scope

Include in main UML:

- `Mucacran_Wa_Ai_Plugin` - punto de Bootstrap (Archivo de arranque) de clases runtime y hooks.
- `Mucacran_Wa_Ai_Admin` - Controller (Controlador) admin activo para configuración, conversaciones y AJAX.
- `Mucacran_Wa_Ai_Config` - Config / Settings class activa usada por admin, webhook y servicios.
- `Mucacran_Wa_Ai_DB` - Repository / DB class (Clase de persistencia / base de datos) activa para tablas, conversaciones y mensajes.
- `Mucacran_Wa_Ai_Webhook_Controller` - Controller (Controlador) REST/webhook activo.
- `Mucacran_Wa_Ai_OpenAI_Service` - Service (Servicio) activo para OpenAI.
- `Mucacran_Wa_Ai_WhatsApp_Service` - Service (Servicio) activo para WhatsApp Cloud API.

Exclude from main UML:

- `Mucacran_Wa_Ai_Hello_World_Controller` - no cargado por el bootstrap activo.
- `Mucacran_Wa_Ai_Hello_World_Model` - solo usado por controlador demo inactivo.
- `Mucacran_Wa_Ai_Otra_Pagina_Model` - placeholder vacío.
- `saludoPersonalizado_Controller` - no cargado por el bootstrap activo.
- `saludoPersonalizado_Model` - solo usado por controlador inactivo.
- `admin/settings-page.php` - vista PHP, no clase.
- `admin/conversations-page.php` - vista PHP, no clase.
- `assets/js/admin.js` - script de UI/AJAX, no clase PHP.

Maybe include in separate "Demo / Legacy Classes" diagram:

- `Mucacran_Wa_Ai_Hello_World_Controller` - ejemplo MVC admin.
- `Mucacran_Wa_Ai_Hello_World_Model` - modelo demo para la vista Hello World.
- `saludoPersonalizado_Controller` - ejemplo MVC para formulario de saludo.
- `saludoPersonalizado_Model` - modelo demo de saludo.
- `Mucacran_Wa_Ai_Otra_Pagina_Model` - placeholder sin comportamiento.

## PART 11 - Final Architecture Summary

El entry point real es `api-whatsaap-base-openai.php`. Este archivo define constantes, carga las siete clases activas, registra `Mucacran_Wa_Ai_DB::activate()` como hook de activación y ejecuta `Mucacran_Wa_Ai_Plugin::init()`.

El comportamiento admin lo maneja `Mucacran_Wa_Ai_Admin`. Esta clase registra menú/submenús, carga assets, renderiza la página de configuración, renderiza la página de conversaciones y atiende los AJAX para leer mensajes y enviar respuestas manuales.

Las solicitudes webhook las maneja `Mucacran_Wa_Ai_Webhook_Controller`. Registra la ruta REST `mucacran-wa-ai/v1/webhook`, verifica el token de configuración, valida firma HMAC opcional, extrae mensajes de texto, guarda el mensaje entrante, llama a OpenAI, envía la respuesta por WhatsApp y guarda el resultado saliente.

OpenAI lo maneja `Mucacran_Wa_Ai_OpenAI_Service`, que usa configuración de `Mucacran_Wa_Ai_Config` y `wp_remote_post()` hacia `https://api.openai.com/v1/chat/completions`.

WhatsApp lo maneja `Mucacran_Wa_Ai_WhatsApp_Service`, que usa configuración de `Mucacran_Wa_Ai_Config` y `wp_remote_post()` hacia Meta Graph API.

La persistencia la maneja `Mucacran_Wa_Ai_DB`. Esta clase crea dos tablas custom, guarda conversaciones y mensajes, consulta conversaciones/mensajes y marca conversaciones como leídas.

La configuración la maneja `Mucacran_Wa_Ai_Config`, usando WordPress Options API con la opción `mucacran_wa_ai_settings`.

Las clases `Mucacran_Wa_Ai_Hello_World_Controller`, `Mucacran_Wa_Ai_Hello_World_Model`, `Mucacran_Wa_Ai_Otra_Pagina_Model`, `saludoPersonalizado_Controller` y `saludoPersonalizado_Model` son ejemplos, placeholders o código secundario detectado pero no parte del Runtime flow (Flujo de ejecución) activo, porque sus loaders no se incluyen desde el Bootstrap (Archivo de arranque) principal.
