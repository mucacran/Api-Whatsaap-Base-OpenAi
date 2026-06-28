# UML Class Diagram Report - Api-Whatsaap-Base-OpenAi

## PART 1 - Architecture Summary

La arquitectura principal del plugin gira alrededor de `Mucacran_Wa_Ai_Plugin`, que actúa como punto de entrada. Esta clase inicializa el área administrativa con `Mucacran_Wa_Ai_Admin`, registra el controlador REST `Mucacran_Wa_Ai_Webhook_Controller` y verifica la base de datos mediante `Mucacran_Wa_Ai_DB`.

El flujo administrativo usa páginas de configuración y conversaciones. La configuración se guarda con `Mucacran_Wa_Ai_Config`, mientras que las conversaciones y mensajes se consultan o persisten con `Mucacran_Wa_Ai_DB`. El flujo webhook recibe mensajes desde WhatsApp, valida la firma, guarda mensajes entrantes, pide una respuesta a OpenAI con `Mucacran_Wa_Ai_OpenAI_Service` y envía la respuesta por `Mucacran_Wa_Ai_WhatsApp_Service`.

También existen clases MVC secundarias (`Mucacran_Wa_Ai_Hello_World_Controller`, `Mucacran_Wa_Ai_Hello_World_Model`, `saludoPersonalizado_Controller`, `saludoPersonalizado_Model`, `Mucacran_Wa_Ai_Otra_Pagina_Model`). Están presentes en el código, pero no se cargan desde el archivo principal actual del plugin.

## PART 2 - Class Inventory Table

| Class (Clase) | File path | Responsibility (Responsabilidad) | Key attributes (Atributos clave) | Key methods (Métodos clave) |
|---|---|---|---|---|
| `Mucacran_Wa_Ai_Plugin` | `includes/class-plugin.php` | Bootstrap principal y registro de hooks. | Ninguno | `init()` |
| `Mucacran_Wa_Ai_Admin` | `includes/class-admin.php` | Páginas admin, assets, AJAX y respuestas manuales. | Ninguno | `register_menu()`, `enqueue_assets()`, `render_configuration_page()`, `render_conversations_page()`, `ajax_get_messages()`, `ajax_send_message()` |
| `Mucacran_Wa_Ai_Config` | `includes/class-config.php` | Lectura, guardado y máscara de opciones del plugin. | `OPTION_NAME` | `all()`, `get()`, `save_from_admin()`, `mask_secret()`, `sanitize_secret()` |
| `Mucacran_Wa_Ai_DB` | `includes/class-db.php` | Tablas personalizadas y persistencia de conversaciones/mensajes. | `DB_VERSION_OPTION`, `DB_VERSION` | `activate()`, `maybe_upgrade()`, `find_or_create_conversation()`, `insert_message()`, `get_conversations()`, `get_messages()`, `mark_read()` |
| `Mucacran_Wa_Ai_Webhook_Controller` | `controllers/class-webhook-controller.php` | Rutas REST, verificación webhook, recepción de mensajes y orquestación AI/WhatsApp. | Ninguno | `register_routes()`, `verify_webhook()`, `receive_webhook()`, `is_valid_signature()`, `extract_messages()`, `handle_incoming_message()` |
| `Mucacran_Wa_Ai_OpenAI_Service` | `services/class-openai-service.php` | Solicita respuestas al API de OpenAI. | Ninguno | `create_reply()` |
| `Mucacran_Wa_Ai_WhatsApp_Service` | `services/class-whatsapp-service.php` | Envia mensajes por WhatsApp Cloud API. | Ninguno | `send_text_message()` |
| `Mucacran_Wa_Ai_Hello_World_Controller` | `includes/Controllers/class-hello-world-controller.php` | Controlador admin MVC de ejemplo. | `page_slug`, `hook_suffix`, `submenu` | `register_menu()`, `enqueue_assets()`, `render()`, `get_menu_icon()` |
| `Mucacran_Wa_Ai_Hello_World_Model` | `includes/Models/class-hello-world-model.php` | Provee título y mensaje para la vista Hello World. | Ninguno | `get_title()`, `get_message()` |
| `Mucacran_Wa_Ai_Otra_Pagina_Model` | `includes/Models/class-hello-world-model.php` | Modelo vacío/placeholder. | Ninguno | Ninguno |
| `saludoPersonalizado_Controller` | `includes/Controllers/saludoPersonalizadoControler.php` | Controlador admin para saludo personalizado. | `page_slug` | `__construct()`, `register_menu()`, `renderSaludoPersonalizado()` |
| `saludoPersonalizado_Model` | `includes/Models/class-saludo-personalizado-model.php` | Genera el texto de saludo personalizado. | Ninguno | `saludo()` |

## PART 3 - Relationship Decision Table

| Source class | Target class | Relationship type | Multiplicity | Reason |
|---|---|---|---|---|
| `Mucacran_Wa_Ai_Plugin` | `Mucacran_Wa_Ai_Admin` | Composition (Composición) | `1` | `init()` crea el objeto admin y registra sus hooks como parte del arranque del plugin. |
| `Mucacran_Wa_Ai_Plugin` | `Mucacran_Wa_Ai_Webhook_Controller` | Composition (Composición) | `1` | `init()` crea el controlador webhook y registra sus rutas REST. |
| `Mucacran_Wa_Ai_Plugin` | `Mucacran_Wa_Ai_DB` | Dependency (Dependencia) | `1` | Solo llama métodos estáticos como `maybe_upgrade()`, no almacena ni crea una instancia. |
| `Mucacran_Wa_Ai_Admin` | `Mucacran_Wa_Ai_Config` | Association (Asociación) | `1` | Usa métodos estáticos para leer y guardar configuración durante la página admin. |
| `Mucacran_Wa_Ai_Admin` | `Mucacran_Wa_Ai_DB` | Association (Asociación) | `1` | Consulta y actualiza conversaciones/mensajes durante vistas y AJAX. |
| `Mucacran_Wa_Ai_Admin` | `Mucacran_Wa_Ai_WhatsApp_Service` | Association (Asociación) | `0..1` | Crea temporalmente el servicio al enviar una respuesta manual. |
| `Mucacran_Wa_Ai_Webhook_Controller` | `Mucacran_Wa_Ai_Config` | Association (Asociación) | `1` | Lee tokens y secreto de Meta para verificación. |
| `Mucacran_Wa_Ai_Webhook_Controller` | `Mucacran_Wa_Ai_DB` | Association (Asociación) | `1` | Persiste conversaciones y mensajes entrantes/salientes. |
| `Mucacran_Wa_Ai_Webhook_Controller` | `Mucacran_Wa_Ai_OpenAI_Service` | Association (Asociación) | `0..1` | Crea el servicio solo cuando maneja un mensaje entrante válido. |
| `Mucacran_Wa_Ai_Webhook_Controller` | `Mucacran_Wa_Ai_WhatsApp_Service` | Association (Asociación) | `0..1` | Crea el servicio para enviar la respuesta generada. |
| `Mucacran_Wa_Ai_OpenAI_Service` | `Mucacran_Wa_Ai_Config` | Association (Asociación) | `1` | Lee API key, modelo y system prompt antes de llamar a OpenAI. |
| `Mucacran_Wa_Ai_WhatsApp_Service` | `Mucacran_Wa_Ai_Config` | Association (Asociación) | `1` | Lee token y phone number ID antes de llamar al Graph API. |
| `Mucacran_Wa_Ai_Hello_World_Controller` | `Mucacran_Wa_Ai_Hello_World_Model` | Association (Asociación) | `1` | `render()` instancia el modelo para obtener datos de la vista. |
| `saludoPersonalizado_Controller` | `saludoPersonalizado_Model` | Association (Asociación) | `1` | `renderSaludoPersonalizado()` instancia el modelo para crear el saludo. |
| `Mucacran_Wa_Ai_Webhook_Controller` | WordPress REST API | Dependency (Dependencia) | `1` | Usa `WP_REST_Request`, `WP_REST_Response` y `register_rest_route()`. |
| `Mucacran_Wa_Ai_DB` | `wpdb/dbDelta` | Dependency (Dependencia) | `1` | Usa infraestructura de WordPress para tablas y queries. |
| `Mucacran_Wa_Ai_OpenAI_Service` | OpenAI API | Dependency (Dependencia) | `1` | Realiza una solicitud HTTP externa a OpenAI. |
| `Mucacran_Wa_Ai_WhatsApp_Service` | WhatsApp Cloud API | Dependency (Dependencia) | `1` | Realiza una solicitud HTTP externa al Graph API de Meta. |

No se detectó Inheritance (Herencia) ni Nested Class (Clase anidada) entre las clases authored del plugin.

## PART 4 - UML Class Diagram Code

El código PlantUML completo está en `docs/uml-class-diagram.puml`.

## PART 5 - Diagram Explanation

El punto de entrada principal es `Mucacran_Wa_Ai_Plugin`. Desde ahí se crean el administrador (`Mucacran_Wa_Ai_Admin`) y el controlador REST (`Mucacran_Wa_Ai_Webhook_Controller`).

El comportamiento administrativo lo maneja `Mucacran_Wa_Ai_Admin`: registra menús, carga assets, muestra configuración, muestra conversaciones y atiende AJAX. Usa `Mucacran_Wa_Ai_Config` para opciones y `Mucacran_Wa_Ai_DB` para conversaciones/mensajes.

El comportamiento webhook lo maneja `Mucacran_Wa_Ai_Webhook_Controller`: registra la ruta REST, valida token/firma, extrae mensajes, guarda datos, llama a `Mucacran_Wa_Ai_OpenAI_Service` y envía respuestas con `Mucacran_Wa_Ai_WhatsApp_Service`.

La persistencia esta concentrada en `Mucacran_Wa_Ai_DB`, que crea dos tablas y ofrece operaciones estaticas para conversaciones y mensajes. La configuracion esta en `Mucacran_Wa_Ai_Config`, que encapsula opciones de WordPress.

Las relaciones más importantes son las Composition (Composición) desde `Mucacran_Wa_Ai_Plugin` hacia `Mucacran_Wa_Ai_Admin` y `Mucacran_Wa_Ai_Webhook_Controller`, porque definen el arranque del plugin. También son importantes las Association (Asociación) desde el webhook hacia DB/OpenAI/WhatsApp, porque representan el flujo principal de conversación automatizada.
