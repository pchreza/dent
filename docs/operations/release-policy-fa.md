# سیاست رسمی انتشار نسخه‌ها

## هدف

هر نسخه‌ای که برای نصب عمومی تحویل می‌شود باید یک **Release ZIP تمیز، قابل استخراج و قابل نصب** باشد. بستهٔ انتشار نباید به محیط توسعه، اطلاعات محلی یا فایل‌های ساخته‌شدهٔ شخصی وابسته باشد.

## نام نسخه

نام فایل از الگوی زیر پیروی می‌کند:

```text
 dent-release-{version}-cpanel.zip
 dent-release-{version}-cpanel.sha256
```

برای مثال: `dent-release-0.1.2-cpanel.zip`.

## فایل‌های مجاز در Release ZIP

| گروه | وضعیت |
|---|---|
| `app/`, `bootstrap/`, `config/`, `database/`, `public/`, `resources/`, `routes/` | الزامی |
| `vendor/` | ممنوع در Release تمیز؛ روی مقصد با Composer نصب می‌شود |
| `public/build/` و `public/fonts/` | مجاز و لازم برای اجرای رابط و فونت آفلاین |
| `tests/` | در بستهٔ Source/Developer مجاز؛ در بستهٔ MVP عمومی اختیاری و طبق نوع Release مشخص می‌شود |
| `composer.json` و `composer.lock` | الزامی |
| `.env.example` | الزامی |
| `README.md`، `INSTALL.md`، `CHANGELOG.md` و مستندات نصب | الزامی |
| `install-laragon.bat` و اسکریپت‌های نصب | مجاز و مستندشده |

## فایل‌های ممنوع

فایل `.env` واقعی، APP_KEY، رمز دیتابیس، Log، Cache، Session، `storage/app/private`، SQLite محلی، دادهٔ تست، `node_modules/`، `.git/`، فایل‌های موقت سیستم، پوشه‌های IDE، dump دیتابیس و فایل‌های پشتیبان نباید در Release ZIP قرار گیرند. `vendor/` نیز در نسخهٔ تمیز جدید قرار نمی‌گیرد تا بسته سبک، قابل بازتولید و مستقل از سیستم سازنده باشد.

## پیش‌شرط ساخت بسته

پیش از ساخت ZIP باید `composer validate --strict`، `composer audit`، `vendor/bin/pint --test`، `php artisan test`، `php artisan view:cache`، `pnpm run build` و `git diff --check` اجرا شوند. سپس یک کپیٔ Clean بدون vendor، node_modules، `.env` و دادهٔ محلی ساخته می‌شود. بعد از ZIP، فهرست آن با `unzip -l` بررسی و نبود فایل‌های ممنوع با کنترل ماشینی تأیید می‌گردد.

## نصب روی مقصد

در مقصدی که SSH دارد، پس از Extract باید `composer install --no-interaction --prefer-dist --no-dev --optimize-autoloader` اجرا شود. در cPanel بدون SSH، Composer باید از طریق **Setup Node.js/PHP App، Terminal داخلی، ابزار Composer شرکت هاست یا یک اسکریپت نصب از داخل Document Root امن** اجرا شود؛ اجرای Composer از مرورگر عمومی توصیه نمی‌شود. راهکار بدون SSH در `docs/operations/cpanel-no-ssh-fa.md` آمده است.

## اصل تکرارپذیری

هر ZIP باید از Commit مشخص ساخته شود، SHA256 داشته باشد و نسخهٔ Commit، تست‌ها، وضعیت audit و دستور نصب در `RELEASE-MANIFEST.txt` درج شود. هر به‌روزرسانی باید README گیت‌هاب و CHANGELOG را در همان Commit تغییر دهد.
