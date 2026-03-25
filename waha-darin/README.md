<p align="center"><img src="https://res.cloudinary.com/dtfbvvkyp/image/upload/v1566331377/laravel-logolockup-cmyk-red.svg" width="400"></p>

## Running the project (port 8000)

- **Frontend (Nuxt)** runs on **port 8000**. From project root: `cd client && npm run dev`. Open http://localhost:8000.
- **Backend (Laravel)** must use another port when both run: `php artisan serve --port=8001`. Set **`API_URL=http://127.0.0.1:8001/api`** in the **Laravel root** `.env` (Nuxt reads that file at build/dev time). Without it, the SPA defaults to same-origin `/api`, which only works when the API is on the same host/port as the site.
- To run **only the backend** (e.g. with a built SPA in `public/_nuxt`): `php artisan serve --port=8000`.

<p align="center">
<a href="https://travis-ci.org/laravel/framework"><img src="https://travis-ci.org/laravel/framework.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://poser.pugx.org/laravel/framework/d/total.svg" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://poser.pugx.org/laravel/framework/v/stable.svg" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://poser.pugx.org/laravel/framework/license.svg" alt="License"></a>
</p>

## About Laravel

Laravel is a web application framework with expressive, elegant syntax. We believe development must be an enjoyable and creative experience to be truly fulfilling. Laravel takes the pain out of development by easing common tasks used in many web projects, such as:

- [Simple, fast routing engine](https://laravel.com/docs/routing).
- [Powerful dependency injection container](https://laravel.com/docs/container).
- Multiple back-ends for [session](https://laravel.com/docs/session) and [cache](https://laravel.com/docs/cache) storage.
- Expressive, intuitive [database ORM](https://laravel.com/docs/eloquent).
- Database agnostic [schema migrations](https://laravel.com/docs/migrations).
- [Robust background job processing](https://laravel.com/docs/queues).
- [Real-time event broadcasting](https://laravel.com/docs/broadcasting).

Laravel is accessible, powerful, and provides tools required for large, robust applications.

## Learning Laravel

Laravel has the most extensive and thorough [documentation](https://laravel.com/docs) and video tutorial library of all modern web application frameworks, making it a breeze to get started with the framework.

If you don't feel like reading, [Laracasts](https://laracasts.com) can help. Laracasts contains over 1500 video tutorials on a range of topics including Laravel, modern PHP, unit testing, and JavaScript. Boost your skills by digging into our comprehensive video library.

## Laravel Sponsors

We would like to extend our thanks to the following sponsors for funding Laravel development. If you are interested in becoming a sponsor, please visit the Laravel [Patreon page](https://patreon.com/taylorotwell).

- **[Vehikl](https://vehikl.com/)**
- **[Tighten Co.](https://tighten.co)**
- **[Kirschbaum Development Group](https://kirschbaumdevelopment.com)**
- **[64 Robots](https://64robots.com)**
- **[Cubet Techno Labs](https://cubettech.com)**
- **[Cyber-Duck](https://cyber-duck.co.uk)**
- **[British Software Development](https://www.britishsoftware.co)**
- **[Webdock, Fast VPS Hosting](https://www.webdock.io/en)**
- **[DevSquad](https://devsquad.com)**
- [UserInsights](https://userinsights.com)
- [Fragrantica](https://www.fragrantica.com)
- [SOFTonSOFA](https://softonsofa.com/)
- [User10](https://user10.com)
- [Soumettre.fr](https://soumettre.fr/)
- [CodeBrisk](https://codebrisk.com)
- [1Forge](https://1forge.com)
- [TECPRESSO](https://tecpresso.co.jp/)
- [Runtime Converter](http://runtimeconverter.com/)
- [WebL'Agence](https://weblagence.com/)
- [Invoice Ninja](https://www.invoiceninja.com)
- [iMi digital](https://www.imi-digital.de/)
- [Earthlink](https://www.earthlink.ro/)
- [Steadfast Collective](https://steadfastcollective.com/)
- [We Are The Robots Inc.](https://watr.mx/)
- [Understand.io](https://www.understand.io/)
- [Abdel Elrafa](https://abdelelrafa.com)
- [Hyper Host](https://hyper.host)
- [Appoly](https://www.appoly.co.uk)
- [OP.GG](https://op.gg)
- [云软科技](http://www.yunruan.ltd/)

## Contributing

Thank you for considering contributing to the Laravel framework! The contribution guide can be found in the [Laravel documentation](https://laravel.com/docs/contributions).

## Code of Conduct

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

If you discover a security vulnerability within Laravel, please send an e-mail to Taylor Otwell via [taylor@laravel.com](mailto:taylor@laravel.com). All security vulnerabilities will be promptly addressed.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
"# Waha-darin"

## Weekly DHL Fulfillment

Use this flow to prepare weekly shipments without a full DHL integration:

1. Sign in to the Voyager admin and open `/admin/weekly-orders` on desktop or phone.
2. Tap any order header to expand the full recipient, address, and book details.
3. Assign a DHL shipment number, then press **Confirm** to save it and mark the order as shipped.
4. If an order cannot be fulfilled, add an optional note and press **Cancel** to notify the customer.
5. After confirmation, the customer will see a “Track Shipment” button pointing to DHL using the stored shipment number.

Orders stay on this page for seven days and disappear immediately after confirmation or cancellation, keeping the list focused on the current week.

## Frontend (Nuxt SPA)

The storefront lives under `client/` and is a Nuxt 2 single-page application configured in `client/nuxt.config.js`. Key environment variables are loaded via `dotenv` when you run any npm script:

- `APP_URL`: Laravel base URL (e.g. `http://127.0.0.1:8000`); used to build the default `API_URL`.
- `API_URL`: Optional override for the backend API endpoint. Defaults to `${APP_URL}/api`.
- `APP_NAME`: Application title used in the Nuxt head metadata.
- `APP_LOCALE`: Default locale loaded on first render.
- `GITHUB_CLIENT_ID`: Enables the GitHub OAuth button when present.

Install the frontend dependencies with `npm install` (or `yarn`) from `client/` before running any build commands.

### Serve the SPA through Laravel

1. Use Node.js 16 LTS (Nuxt 2.x is incompatible with the latest Node 20+/24 releases). If you use `nvm`, run `nvm use` inside `client/` before building.
2. Ensure your Laravel `.env` contains `APP_URL=http://127.0.0.1:8000` (and set `API_URL` if the API lives elsewhere).
3. From `client/`, build the static assets: `npm run generate`. This runs `nuxt generate` and copies the output into `public/_nuxt`.
4. In the project root, start Laravel on port 8000: `php artisan serve --host=127.0.0.1 --port=8000`.

Laravel now serves both the API and the generated SPA from `http://127.0.0.1:8000`, so you no longer need the Nuxt dev server on port 3000.
