Plan Complet de Dezvoltare: Platformă Management Regim Hotelier \& Mentenanță

## Stadiu implementare (actualizat 2026-10-19)

Detalii și jurnal pe sesiuni: vezi `PROGRES.md`.

| Etapă | Status | Note |
|---|---|---|
| Etapa 1 — Fundație (proiect, migrații ~24 tabele, auth, 4 roluri RBAC) | ✅ | Laravel 13, Breeze+Fortify, Spatie Permission, 2FA obligatoriu pt. SuperAdmin/Host |
| Etapa 2 — MVP rezervări + căutare geo | ✅ | Căutare prin **Haversine** (MySQL), fișă proprietate, rezervare cu anti-overbooking (`lockForUpdate`), review/rating, iCal sync (Airbnb/Booking) |
| Etapa 3 — Modulul de mentenanță | ✅ | Auto-task la check-out (`BookingCheckedOut` → `CreateCleaningTask`, alocare după firmă preferată/oraș), panou Provider, echipe interne + delegare, proof of work (poze before/after), raport de facturare |
| Etapa 4 — SuperAdmin, Abonamente și Plăți | ✅ | **Done**: panou Filament admin, impersonation, approval KYC (host/provider), CRUD planuri, Stripe Connect (host onboarding) + checkout `payment_intent` + webhook; **SaaS**: abonamente Cashier forțate pe host (middleware `subscription` + pagină `/host/subscription`), `application_fee` + `transfer_data` la rezervare, sincronizare webhook `customer.subscription.*`, widget MRR/churn în admin. **Rămas producție**: chei Stripe live, `STRIPE_WEBHOOK_SECRET`, prețuri/planuri reale |
| Etapa 5 — Notificări, securitate, lansare | 🟦 | **Done**: emailuri tranzacționale booking (confirmare/anulare), 2FA, audit (`owen-it/laravel-auditing`), CI (Pint+PHPStan+teste pe MySQL/Redis), **mesagerie internă** (`Conversation`/`Message`, pagini Volt `/messages`) + **notificări in-app** (canal `database`, `NotificationBell` în toate panourile). **Rămas**: Twilio SMS (coduri check-in), Mailgun/Postmark producție, deploy Forge + SSL |
| Faza 2 — Administrare conturi + comunicare | 🟦 | **Done**: resursă Filament „Utilizatori" (CRUD, roluri, suspendare/reactivare, reset parolă, impersonation), middleware `EnsureAccountActive`, mesagerie internă + notificări in-app. **Rămas**: profil completat (badge profesional) + dashboard per tip cont (Guest `/dashboard` încă placeholder) |

**Decizii care diferă de planul inițial:**
- **DB: MySQL 8.4 nativ Laragon** (nu PostgreSQL 16 + PostGIS). Geo-căutare prin formulă Haversine (`whereRaw`); anti-overbooking prin `lockForUpdate` în tranzacție (fără constrângere `EXCLUDE`).
- **Filament 5** (nu 3) — 2 panouri: `admin` (SuperAdmin) și `provider` (Provider). Panoul **Host** rămâne pe **Livewire/Volt** (nu Filament).
- **Redis 5 + client predis** (fără `phpredis` pe Windows); **Horizon doar în CI** (Linux), local `queue:work`.
- **Storage**: MediaLibrary pe discul local/`public` (S3/DO Spaces rămâne post-lansare).

---

​1. Arhitectura Bazei de Date \& Structura de Roluri (RBAC)

​Pentru ca sistemul să fie modular, vom folosi un sistem de roluri stricte (Role-Based Access Control). Un utilizator poate avea unul sau mai multe dintre următoarele roluri:

​SuperAdmin (Tu): Controlează întreaga platformă, abonamentele, utilizatorii și setările globale.

​Proprietar / Firmă Management (Host): Deține proprietățile, gestionează prețurile, rezervările și angajează firmele de curățenie.

​Firme / Personal Mentenanță (Provider): Primesc task-uri de curățenie sau reparații, își gestionează echipele proprii.

​Chiriaș (Guest): Clientul final care caută, rezervă și plătește cazarea.

​2. Matricea de Funcționalități pe Module

​Modulul 1: Aplicația Publică (Chiriaș / Guest)

​Motor de Căutare Geo-Localizat: Filtrare după oraș, rază de kilometri (folosind funcțiile spatiale din PostgreSQL/MySQL), perioadă și număr de oaspeți.

​Fișa Proprietății: Galerie foto, descriere, facilități (Wi-Fi, AC, parcare), recenzii și integrare Google Maps.

​Sistem de Rezervare Directă: Alegerea perioadei din calendar (cu blocare instantanee pentru a evita overbooking-ul).

​Check-in / Check-out Digital: Instrucțiuni automate trimise pe email/SMS cu codul de la seif/yala inteligentă, regulile casei și parola de Wi-Fi.

​Modul de Raportare Probleme: În timpul șederii, chiriașul poate deschide un tichet direct din aplicație (ex: "S-a ars un bec", "Nu curge apă caldă") și poate atașa o poză.

​Modulul 2: Panoul Proprietarului (Dashboard Host)

​Managementul Proprietăților: Adăugare/Editare apartamente, prețuri dinamice (ex: preț de weekend, preț de sezon).

​Sistem de Calendar Centralizat (Channel Manager de bază):

​Sincronizare bidirecțională prin iCal cu Airbnb și Booking.com.

​Dacă un apartament se rezervă în aplicația ta, perioada se blochează automat pe Airbnb.

​Dispecerat Mentenanță:

​Setare reguli automate: „Când un chiriaș face check-out, creează automat un task de curățenie și alocă-l Firmei X”.

​Vizualizarea statusului în timp real (Curat, Necurat, În curs de curățare, Necesită reparații).

​Rapoarte Financiare: Grafice cu gradul de ocupare, veniturile generate și cheltuielile cu mentenanța.

​Modulul 3: Panoul Firmei de Mentenanță (Dashboard Provider)

​Vizualizare Task-uri (Listă \& Calendar): Task-urile primite de la proprietari, sortate după prioritate și ora de check-out a oaspetelui.

​Managementul Echipei Interne: Firmele mari pot adăuga „angajați” (cleaneri/tehnicieni) și le pot delega task-urile primite.

​Proof of Work (Dovada muncii): Opțiunea de a încărca poze "Înainte" și "După" finalizarea curățeniei sau reparației înainte de a închide task-ul.

​Facturare: Generarea automată a unui raport lunar cu toate task-urile efectuate pentru un anumit proprietar, gata pentru a fi facturate.

​3. Modulul de SuperAdmin (Sistemul Tău de Control)

​Acesta este panoul tău central de comandă, dezvoltat rapid în Laravel folosind Laravel Filament.

​Funcționalități de Administrare și Securitate:

​User Impersonation (Logare în numele utilizatorului): Un buton „Loghează-te ca acest utilizator” care îți permite să vezi exact ce vede un proprietar sau o firmă de curățenie în panoul lor, fără să le știi parola (esențial pentru suport tehnic rapid).

​Aprobare / Verificare Conturi (KYC de bază): Proprietarii și firmele de mentenanță trebuie să încarce un CUI sau un act de identitate înainte ca proprietățile lor să devină publice (pentru a evita fraudarea clienților).

​Sistem de Dispute: Un ecran unde poți arbitra problemele (ex: un proprietar refuză să plătească o curățenie pe motiv că nu a fost făcută bine; tu poți vedea pozele încărcate de firmă și istoricul apelurilor).

​Managementul Abonamentelor (SaaS Engine):

​Configurator de Planuri: Crearea de planuri tarifare direct din interfață (ex: Plan Basic - 19€/lună, Plan Premium - 49€/lună).

​Managementul Comisioanelor: Setarea procentajului pe care platforma îl oprește din plățile clienților sau din task-urile de mentenanță (ex: 3% global sau 5% personalizat pentru un anumit client mare).

​Sistem Metrics (Sănătatea Afacerii): Grafice live cu MRR (Monthly Recurring Revenue), Churn Rate (câți clienți renunță la abonament) și numărul de rezervări totale din platformă.



Etape Detaliate:

​Etapa 1: Fundația și Baza de Date (Săptămânile 1-2)

​Configurarea proiectului Laravel 11.

​Crearea migrațiilor pentru baza de date (Tabelele: users, properties, bookings, tasks, subscriptions).

​Implementarea sistemului de autentificare (Laravel Breeze sau Jetstream) și rutarea de bază pentru cele 4 roluri.

​Etapa 2: MVP-ul de Rezervări și Căutare (Săptămânile 3-5)

​Dezvoltarea interfeței pentru chiriași (Căutarea pe bază de locație + filtrare).

​Logica din backend pentru rezervare: Algoritmul care verifică dacă proprietatea este liberă în perioada selectată.

​Implementarea parserului iCal (pentru a citi calendarele din Airbnb/Booking).

​Etapa 3: Modulul de Mentenanță (Săptămânile 6-8)

​Crearea interfeței pentru Firmele de Curățenie.

​Sistemul de evenimente din Laravel: Când o rezervare ajunge la final, se declanșează un BookingEndedEvent care creează automat un task în tabela tasks.

​Sistemul de upload de imagini (stocate securizat pe un serviciu precum AWS S3 sau DigitalOcean Spaces) pentru dovada curățeniei.

​Etapa 4: SuperAdmin, Abonamente și Plăți (Săptămânile 9-10)

​Instalarea și configurarea Laravel Filament pentru panoul tău de SuperAdmin.

​Integrarea Laravel Cashier (Stripe): Gestionarea automată a cardurilor salvate, a perioadelor de tip "Free Trial", generarea de facturi în format PDF și blocarea accesului la aplicație dacă abonamentul a expirat.

​Integrarea sistemului de Webhooks de la Stripe (ca platforma să știe instant când o plată a eșuat sau a fost procesată cu succes).

​Etapa 5: Notificări, Securitate și Lansare (Săptămânile 11-12)

​Integrare Twilio (pentru notificări prin SMS cu codurile de acces la check-in) și Mailgun/Postmark (pentru email-urile tranzacționale).

​Scrierea testelor automate (Feature Tests în Pest sau PHPUnit) pentru procesul de rezervare și plată, asigurându-te că nicio linie nouă de cod nu va genera erori în logica de rezervare.

​Deploiement pe server (folosind Laravel Forge pentru o gestionare ușoară a serverului pe AWS sau DigitalOcean), configurarea certificatului SSL și activarea cozilor de așteptare (Redis Queue) pentru rularea rapidă a aplicației.

​5. Strategia de Integrare și Rentabilitate Maximă

​Pentru ca aplicația să fie profitabilă din punct de vedere tehnic și operațional încă din prima zi, urmează aceste trei reguli:

​Decuplează Prezentarea de Aplicație: Instalează WordPress pe domeniul principal (site.ro) pentru SEO, blog și campaniile de marketing pe rețelele sociale. Aplicația ta Laravel va rula pe subdomeniul app.site.ro. Astfel, marketingul nu va încetini niciodată codul aplicației.

​Folosește Webhooks pentru Automatizare Social Media: Poți programa un mic serviciu în Laravel care, atunci când o proprietate primește o recenzie de 5 stele de la un chiriaș, trimite automat un API call către un instrument precum Zapier sau Make.com pentru a posta automat o grafică pe Instagram-ul sau Facebook-ul platformei tale: "O nouă experiență de 5 stele în Brașov prin platforma noastră!".

​Focalizare pe Retenție (Churn Rate): În panoul de SuperAdmin, setează o alertă automată: dacă un proprietar nu a mai intrat în aplicație de 7 zile, sistemul să îți trimită un email. Îl poți suna direct pentru a-i cere feedback. În faza de început, relația directă cu primii clienți îți va dicta succesul pe termen lung.

