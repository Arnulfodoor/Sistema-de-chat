# 💬 ChatApp

> Sistema de chat en tiempo real construido con PHP y MySQL. Diseño oscuro inspirado en Discord/WhatsApp, con panel de administración completo, gestión de usuarios y cierre automático de sesión por inactividad.

<img width="1061" height="579" alt="image" src="https://github.com/user-attachments/assets/90a26212-a1ba-47ae-b55d-8f8f4e21dbb8" />


Admin panel:

<img width="797" height="247" alt="image" src="https://github.com/user-attachments/assets/9f5b584b-cbd0-4200-9dd5-d8ca2593d90f" />


---

## ✨ Características

- 🔐 **Solo login** — sin registro público, el admin crea todas las cuentas
- 👑 **Panel de administración** completo para gestionar usuarios y chats
- 💬 **Chats privados y grupos** con soporte para múltiples miembros
- ⚡ **Mensajes en tiempo real** mediante polling cada 2.5 segundos
- ⏱️ **Cierre automático de sesión** tras 15 minutos de inactividad (configurable)
- 🟢 **Indicador de presencia** online/offline por usuario
- 🔔 **Badge de mensajes no leídos** en el sidebar
- 🗑️ **Eliminación de usuarios y chats** desde el panel admin
- 🔒 **Seguridad robusta**: PDO con prepared statements, bcrypt, CSRF, session timeout
- 📱 **Diseño responsivo** adaptado a escritorio y móvil

---

## 📸 Vista previa

```
┌─────────────────────────────────────────────────────┐
│  💬 ChatApp          💬 Chats  ⚙️ Admin   ⏱️ 14:32  │
├──────────────┬──────────────────────────────────────┤
│ Conversacion │                                      │
│ ─────────── │   Hola equipo! 👋              10:30 │
│ 🟢 Juan  2  │                                      │
│ ─────────── │          Buenas, ¿cómo va? ✓  10:31 │
│ 👥 General  │                                      │
│ ─────────── │   Todo bien, empezamos ya      10:32 │
│             │ ──────────────────────────────────── │
│             │  Escribe un mensaje...           ➤   │
└─────────────┴──────────────────────────────────────┘
```

---

## 🚀 Instalación

### Requisitos

| Tecnología | Versión mínima |
|------------|---------------|
| PHP        | 8.0+          |
| MySQL      | 5.7+ / 8.0    |
| MariaDB    | 10.4+ (alternativa a MySQL) |
| Servidor   | Apache / Nginx / PHP built-in |

> **Credenciales por defecto:**  
> Usuario: `admin` · Contraseña: `admin123`  
---

## 📁 Estructura del proyecto

```
chatapp/
├── index.php             
├── chat.php             
├── database.sql          
│
├── config/
│   ├── database.php      
│   └── auth.php           
│
├── api/
│   ├── chats.php         
│   ├── messages.php       
│   ├── users.php          
│   └── logout.php        
│
└── assets/
    ├── css/style.css     
    └── js/app.js          
```


## 🔒 Seguridad

Este proyecto implementa las siguientes medidas de seguridad:

- **PDO con prepared statements** — previene inyección SQL
- **`password_hash()` con bcrypt** — almacenamiento seguro de contraseñas
- **Token CSRF** — protección contra ataques Cross-Site Request Forgery
- **Sesiones con timeout** — cierre automático por inactividad
- **`htmlspecialchars()`** en todas las salidas — previene XSS
- **`cookie_httponly`** — las cookies no son accesibles desde JavaScript
- **Sin registro público** — solo el administrador puede crear usuarios

## 📄 Licencia

Este proyecto está licenciado bajo la **MIT License**, lo que significa que puedes:

- ✅ Usar este proyecto de forma privada o comercial
- ✅ Modificarlo y adaptarlo a tus necesidades
- ✅ Distribuirlo y sublicenciarlo
- ✅ Incluirlo en proyectos propietarios

La única condición es mantener el aviso de copyright original.

```
MIT License

Copyright (c) Daniel Puerta 2026

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all
copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
SOFTWARE.
```

---

<p align="center">Hecho con ❤️ · PHP + MySQL · Licencia MIT</p>
