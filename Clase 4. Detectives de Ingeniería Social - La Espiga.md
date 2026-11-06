# Detectives de Ingeniería Social — Panadería Digital La Espiga

**Pablo Nieto Pérez**

Seguridad de Aplicaciones

Tecnología en Desarrollo de Software para Negocios Digitales, Universidad de Manizales

24 de Septiembre de 2026

Guía: `4 Guia del Estudiante - Detectives de Ingeniería Social.pdf`

---

## Paso 4 — Análisis de los seis escenarios

| # | ¿Es ingeniería social? | Canal | Técnica | Principio psicológico | Principio de seguridad comprometido | Señales de alerta identificadas |
|---|---|---|---|---|---|---|
| 1 | Sí | Correo electrónico | Phishing (genérico, no dirigido) | Urgencia, con apoyo de autoridad implícita del «soporte» | Autenticidad y confidencialidad | El dominio `negocio-seguro-ayuda.com` no es el oficial. El enlace `negoci0seguro-verificacion.com` usa un cero en vez de la «o». Saludo genérico («Estimado usuario»). Plazo amenazante de 2 horas. |
| 2 | Sí | Teléfono | Vishing + pretexting | Autoridad («de Sistemas de la Universidad») + urgencia («problema urgente») | Confidencialidad (si se entrega la contraseña) y autenticidad | Pide usuario y contraseña de viva voz, algo que ningún área de sistemas legítima hace. Solo da un nombre de pila, imposible de verificar. Presiona con un «problema urgente» sin dar detalles. |
| 3 | **No, es legítimo** | Correo electrónico | No aplica | No aplica | Ninguno | No hay señales de alerta sino de legitimidad: usa el nombre real («Hola Juan»), no trae enlace y pide entrar normalmente al dominio oficial `negocioseguro.com`, da un plazo razonable de 30 días sin amenazas y aclara que no hay que hacer nada. |
| 4 | Sí | Medios físicos removibles (USB) | Baiting | Escasez: la etiqueta promete información exclusiva («CONFIDENCIAL - NO COMPARTIR») y explota la curiosidad | Confidencialidad, integridad y disponibilidad (al conectarla puede instalar malware) | USB sin dueño en un lugar común. Etiqueta pensada para despertar curiosidad. Nadie deja la nómina «por accidente» en la sala de descanso. |
| 5 | Sí | Redes sociales | Phishing genérico, con una oferta de empleo como cebo | Urgencia / escasez («2 horas», «antes de que se asigne a otro candidato»), apoyada en la simpatía del halago («¡Felicidades!») | Confidencialidad y autenticidad | No se postuló a nada. La empresa no se nombra («una empresa reconocida»). Plazo artificial de 2 horas. Enlace externo para «confirmar». |
| 6 | Sí | En persona / físico | Tailgating / piggybacking + pretexting | Simpatía (pide ayuda) + autoridad implícita del chaleco de «Servicio técnico» | Confidencialidad, integridad y disponibilidad (acceso físico no autorizado) y autenticidad (nadie verifica quién es) | No muestra credencial ni está anunciado. Pide pasar a un área restringida. Usa las manos ocupadas como pretexto para que el empleado no verifique. |

El escenario 3 es el legítimo: no todos los casos son ataques.

---

## Paso 5 — Escenario propio

> La guía pide un caso real que alguien del equipo haya recibido. Este es un fraude real y muy común en Colombia; si alguien del equipo tiene uno propio en su WhatsApp, SMS o correo, cámbienlo por ese antes de entregar.

**Escenario 7 (SMS):**

```
Servientrega: Su paquete está retenido por dirección incompleta.
Actualice sus datos y pague $3.500 de reenvío en 24h:
servientrega-co.entregas-app.com
```

| # | ¿Es ingeniería social? | Canal | Técnica | Principio psicológico | Principio de seguridad comprometido | Señales de alerta identificadas |
|---|---|---|---|---|---|---|
| 7 | Sí | SMS / mensajería instantánea | Smishing + pretexting | Urgencia (24 h) + autoridad de una marca conocida | Confidencialidad (datos de la tarjeta) y autenticidad | El dominio no es el oficial de la empresa. Cobra un monto pequeño para no levantar sospechas: lo que busca son los datos de la tarjeta. Llega aunque no se esté esperando ningún paquete. Plazo corto. |

---

## Paso 6 — Revisión final

- [x] La tabla de análisis de los seis escenarios, completa (canal, técnica, principio psicológico, principio de seguridad, señales de alerta).
- [x] La correcta identificación del escenario legítimo: el escenario 3.
- [x] El escenario propio diseñado, con canal, técnica, descripción y señal de alerta.
