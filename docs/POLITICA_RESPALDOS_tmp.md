#  INSTITUTO DOMINICANO DE METEOROLOGÍA (INDOMET)
## DEPARTAMENTO DE TECNOLOGÍA DE LA INFORMACIÓN

---

# POLÍTICA DE RESPALDOS Y RECUPERACIÓN
**SISTEMA DE RESERVAS DE SALÓN**

| **Código:** POL-BCK-004 | **Clasificación:** USO INTERNO | **Revisión:** 2026 |
| :--- | :--- | :--- |

---

## 1. OBJETIVO
Establecer los procedimientos y normas para garantizar la disponibilidad e integridad de los datos del **Sistema de Reservas de Salón**, mediante la ejecución periódica de copias de seguridad (backups), en cumplimiento con la norma **NORTIC A6:2016** (Secciones 4.01 y 4.02).

---

## 2. FRECUENCIA Y RETENCIÓN

| Tipo de Respaldo | Frecuencia | Hora de Ejecución | Retención | Contenido |
|------------------|------------|-------------------|-----------|-----------|
| **Incremental/Diario** | Diario | 23:00 Horas | 30 días | `usuarios.db`, `reservas.db` |
| **Completo/Mensual** | Mensual (Día 1) | 01:00 Horas | 12 meses | Directorio `database/` completo |

---

## 3. PROCEDIMIENTO DE RESPALDO (AUTOMATIZACIÓN)

El sistema utiliza un script de mantenimiento (`scripts/backup.php`) que ejecuta las siguientes tareas:
1.  **Validación de Integridad:** Comprueba que los archivos .db no estén corruptos.
2.  **Snapshot:** Genera una copia con estampa de tiempo (`_YYYY-MM-DD_HH-mm-ss.db`).
3.  **Almacenamiento Seguro:** Mueve la copia al directorio protegido `/backups/`.
4.  **Rotación:** Elimina automáticamente copias que excedan la política de retención.

### Cron Job (Configuración del Servidor)
```bash
# Ejecución diaria a las 11:00 PM
0 23 * * * php /var/www/html/reservas/scripts/backup.php >> /var/log/reservas_backup.log 2>&1
```

---

## 4. PROTOCOLO DE RESTAURACIÓN

En caso de fallo crítico o pérdida de datos, el personal de TI procederá a:
1.  Detener el servicio web (Modo Mantenimiento).
2.  Localizar la copia válida más reciente en `/backups/`.
3.  Reemplazar los archivos dañados en `/database/`.
4.  Ejecutar scripts de validación de esquema.
5.  Restaurar el servicio.

---

## 5. ALMACENAMIENTO SEGURO
*   **Local:** Directorio protegido contra escritura web y listado de directorios.
*   **Externo:** Sincronización semanal con el servidor de archivos central del INDOMET (NAS/Cloud).

---

**Gerencia de Infraestructura Tecnológica**
INDOMET
