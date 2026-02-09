# 🌦️ Sistema de Reservas del Salón
## Indomet - Instituto Dominicano de Meteorología

![Version](https://img.shields.io/badge/version-2.1.0-blue.svg?style=for-the-badge)
![License](https://img.shields.io/badge/license-MIT-green.svg?style=for-the-badge)
![Status](https://img.shields.io/badge/status-stable-success.svg?style=for-the-badge)
![NORTIC](https://img.shields.io/badge/NORTIC-A6:2016-orange.svg?style=for-the-badge)

---

### 🏛️ Sobre el Proyecto

Sistema de gestión integral para las reservaciones del **Salón Julio Rib Santa María**. Diseñado para optimizar el flujo de trabajo del INDOMET, garantizando transparencia, control y eficiencia en el uso de los espacios institucionales.

> *"Ciencia y Servicio por un Desarrollo Sostenible"*

---

### �️ Tecnologías Implementadas

El núcleo del sistema está construido sobre tecnologías robustas y modernas:

| Lenguaje / Herramienta | Uso | Badge |
|------------------------|-----|-------|
| **PHP 8.x** | Backend Logic | ![PHP](https://img.shields.io/badge/php-%23777BB4.svg?style=flat-square&logo=php&logoColor=white) |
| **SQLite 3** | Base de Datos | ![SQLite](https://img.shields.io/badge/sqlite-%2307405e.svg?style=flat-square&logo=sqlite&logoColor=white) |
| **HTML5 & CSS3** | Frontend | ![HTML5](https://img.shields.io/badge/html5-%23E34F26.svg?style=flat-square&logo=html5&logoColor=white) ![CSS3](https://img.shields.io/badge/css3-%231572B6.svg?style=flat-square&logo=css3&logoColor=white) |
| **JavaScript (ES6)** | Interactividad | ![JavaScript](https://img.shields.io/badge/javascript-%23323330.svg?style=flat-square&logo=javascript&logoColor=%23F7DF1E) |
| **GSAP** | Animaciones | ![GreenSock](https://img.shields.io/badge/GSAP-88CE02?style=flat-square&logo=greensock&logoColor=white) |
| **Composer** | Dependencias | ![Composer](https://img.shields.io/badge/Composer-885630?style=flat-square&logo=composer&logoColor=white) |

---

### ✨ Características Principales

*   **📅 Gestión Visual:** Calendario interactivo con navegación fluida (AJAX) y transiciones animadas.
*   **🌓 Modo Adaptable:** Soporte nativo para Modo Claro y Oscuro con branding dinámico.
*   **🛡️ Seguridad Robusta:** Sistema de roles jerárquicos (Admin, Manager, Usuario) y contraseñas encriptadas.
*   **📊 Reportes:** Exportación de datos compatible con Excel y métricas en tiempo real.
---

### 🚀 Instalación Rápida

```bash
# 1. Clonar repositorio
git clone https://github.com/indomet/reservas-salon.git

# 2. Configurar permisos
chmod 775 database public/uploads

# 3. Iniciar servidor (Dev)
php -S localhost:8000 -t public
```

Para una guía detallada, consulte el [Manual de Instalación](docs/MANUAL_INSTALACION.md).

---

### 📂 Estructura del Repositorio

    .
    ├── config/          # ⚙️ Configuración global
    ├── database/        # 💾 Archivos SQLite (Seguros)
    ├── docs/            # 📚 Documentación Institucional
    ├── public/          # 🌐 Entry Point (Web Root)
    ├── scripts/         # 🤖 Scripts de Mantenimiento
    ├── src/             # 🧠 Lógica del Sistema (MVC)
    ├── templates/       # 🎨 Vistas y Layouts
    └── README.md        # 📖 Este archivo

---

<div align="center">
  <p>Desarrollado por el Departamento de Tecnología de la Información</p>
  <p><b>INDOMET - 2026</b></p>
</div>
