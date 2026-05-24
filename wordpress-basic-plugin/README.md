# WordPress Basic Plugin

Plugin basico de WordPress con:

- Pagina propia en el menu lateral de WordPress: `Basic Plugin`.
- Opcion configurable `wbp_message`.
- Shortcode `[basic_plugin_message]`.
- Limpieza de opciones al desinstalar.

## Instalacion

1. Copia la carpeta `wordpress-basic-plugin` dentro de `wp-content/plugins/`.
2. Activa el plugin desde el panel de WordPress.
3. Ve a `Basic Plugin` en el menu lateral para cambiar el mensaje.

## Uso

Inserta el shortcode en una pagina o entrada:

```text
[basic_plugin_message]
```

Tambien puedes cambiar la etiqueta HTML:

```text
[basic_plugin_message tag="div"]
```
