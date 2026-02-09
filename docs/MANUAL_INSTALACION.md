# 🇩🇴 INSTITUTO DOMINICANO DE METEOROLOGÍA (INDOMET)
## DEPARTAMENTO DE TECNOLOGÍA DE LA INFORMACIÓN

---

# MANUAL DE INSTALACIÓN Y DESPLIEGUE
**SISTEMA DE RESERVAS DE SALÓN V2.1.0**

| **Código:** MAN-INS-002 | **Clasificación:** USO TÉCNICO | **Revisión:** 2026 |
| :--- | :--- | :--- |

---

## 1. PROPÓSITO

Este documento técnico detalla los procedimientos estandarizados para la instalación, configuración y puesta en marcha del Sistema de Reservas en los servidores del INDOMET.

---

## 2. REQUISITOS DEL ENTORNO

Para garantizar la estabilidad y rendimiento, el servidor debe cumplir con las siguientes especificaciones mínimas:

### 2.1 Software Base
*   **Sistema Operativo:** Linux (Ubuntu Server 20.04 LTS o superior) / Windows Server.
*   **Servidor Web:** Apache HTTP Server 2.4+ o Nginx.
*   **Intérprete:** PHP 7.4 o superior (Recomendado PHP 8.1).
*   **Base de Datos:** SQLite 3 (Nativo).

### 2.2 Dependencias PHP
Es mandatorio habilitar las siguientes extensiones en `php.ini`:
*   `extension=pdo_sqlite`
*   `extension=sqlite3`
*   `extension=mbstring`
*   `extension=fileinfo`

---

## 3. PROCESO DE INSTALACIÓN

### 3.1 Despliegue de Archivos
1.  Transfiera el paquete de instalación al directorio raíz del servidor web.
    *   **Servidor Dedicado:** `/var/www/html/`
    *   **XAMPP/LAMPP:** `htdocs/reservas` (Soporte nativo para subcarpetas)
2.  Verifique la integridad de la estructura de directorios:
    *   `/config`
    *   `/public`
    *   `/src`
    *   `/database`

### 3.1.1 Configuración Apache (XAMPP/LAMPP)
El sistema utiliza URLs amigables, por lo que requiere `mod_rewrite`.
1.  Edite `httpd.conf` y asegúrese de descomentar: `LoadModule rewrite_module modules/mod_rewrite.so`.
2.  Asegúrese de que la configuración del directorio permita `AllowOverride All` para que el archivo `.htaccess` funcione.
    ```apache
    <Directory "/opt/lampp/htdocs">
        AllowOverride All
    </Directory>
    ```

### 3.2 Configuración de Permisos (Linux)
Por seguridad y funcionalidad, establezca los permisos estrictamente necesarios:

```bash
# Asignar propietario al usuario del servidor web (www-data)
chown -R www-data:www-data /var/www/html/reservas

# Permisos de escritura solo en directorios de datos
chmod -R 775 /var/www/html/reservas/database
chmod -R 775 /var/www/html/reservas/public/uploads
```

### 3.3 Inicialización
1.  Navegue a la URL del sistema (Intranet institucional).
2.  El **Asistente de Instalación** verificará el entorno automáticamente.
3.  Siga los pasos en pantalla para generar las bases de datos iniciales.

---

## 4. CONFIGURACIÓN INSTITUCIONAL (BRANDING)

El sistema permite la personalización de la identidad visual a través del Panel de Administración.

1.  Acceda con credenciales de **Administrador**.
2.  Diríjase a: **Configuración** (Icono de engranaje).
3.  **Carga de Activos:**
    *   **Logos:** Suba los archivos vectoriales (.svg) o rasterizados (.png) correspondientes al manual de marca del INDOMET.
    *   **Colores:** Defina el color primario institucional (Hex Code).

---

## 5. MANTENIMIENTO Y RESPALDO

### 5.1 Política de Backups
Debido a la arquitectura *Serverless SQL* (SQLite), el respaldo consiste en la copia íntegra del directorio `/database`.

*   **Frecuencia:** Diaria (Automatizada vía Cron).
*   **Retención:** 30 días.
*   **Ruta de Origen:** `/var/www/html/reservas/database/*.db`

---

**Departamento de Tecnología de la Información**
*División de Desarrollo de Software*
INDOMET
