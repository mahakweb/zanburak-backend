# راهنمای کامل Sitemap.xml و Robots.txt

## 📋 سوال 1: Sitemap و Robots داینامیک

بله، درست است! شما دو route داینامیک دارید:

1. **`/api/sitemap.xml`** - Sitemap داینامیک که از دیتابیس می‌خواند
2. **`/api/robots.txt`** - Robots.txt داینامیک

این routes در `routes/api/public.php` تعریف شده‌اند و توسط `SeoController` مدیریت می‌شوند.

---

## 🔧 تنظیمات Apache

### روش 1: RewriteRule (توصیه می‌شود)

در فایل `.htaccess` در root frontend server خود اضافه کنید:

```apache
# Sitemap.xml - Redirect to backend API
RewriteEngine On
RewriteCond %{REQUEST_URI} ^/sitemap\.xml$ [NC]
RewriteRule ^(.*)$ http://your-backend-url/api/sitemap.xml [P,L]

# Robots.txt - Redirect to backend API
RewriteCond %{REQUEST_URI} ^/robots\.txt$ [NC]
RewriteRule ^(.*)$ http://your-backend-url/api/robots.txt [P,L]
```

**نکته:** `[P,L]` یعنی Proxy و Last rule

### روش 2: Alias (اگر Apache config دسترسی دارید)

در فایل `httpd.conf` یا `apache2.conf`:

```apache
<VirtualHost *:80>
    ServerName zanburak.ir
    DocumentRoot /path/to/frontend/dist
    
    # Sitemap
    ProxyPass /sitemap.xml http://your-backend-url/api/sitemap.xml
    ProxyPassReverse /sitemap.xml http://your-backend-url/api/sitemap.xml
    
    # Robots
    ProxyPass /robots.txt http://your-backend-url/api/robots.txt
    ProxyPassReverse /robots.txt http://your-backend-url/api/robots.txt
</VirtualHost>
```

### روش 3: فایل استاتیک (اگر نمی‌خواهید داینامیک باشد)

اگر می‌خواهید فایل‌های استاتیک داشته باشید:

```bash
# در backend
curl http://your-backend-url/api/sitemap.xml > /path/to/frontend/dist/sitemap.xml
curl http://your-backend-url/api/robots.txt > /path/to/frontend/dist/robots.txt
```

**مشکل:** باید هر بار که محتوا تغییر می‌کند، این فایل‌ها را به‌روز کنید.

---

## 🔧 تنظیمات Nginx

در فایل config Nginx (معمولاً `/etc/nginx/sites-available/zanburak`):

```nginx
server {
    listen 80;
    server_name zanburak.ir;
    root /path/to/frontend/dist;
    index index.html;

    # Sitemap.xml - Proxy to backend
    location = /sitemap.xml {
        proxy_pass http://your-backend-url/api/sitemap.xml;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
    }

    # Robots.txt - Proxy to backend
    location = /robots.txt {
        proxy_pass http://your-backend-url/api/robots.txt;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
    }

    # Frontend routes
    location / {
        try_files $uri $uri/ /index.html;
    }
}
```

---

## 🔍 ثبت در Google Search Console

### مرحله 1: ثبت سایت
1. به https://search.google.com/search-console بروید
2. روی "Add Property" کلیک کنید
3. URL prefix را انتخاب کنید: `https://zanburak.ir`
4. روش تایید را انتخاب کنید (DNS یا HTML file)

### مرحله 2: Submit Sitemap
1. در Google Search Console، به "Sitemaps" بروید
2. در قسمت "Add a new sitemap"، این URL را وارد کنید:
   ```
   https://zanburak.ir/sitemap.xml
   ```
   ⚠️ **مهم:** فقط `/sitemap.xml` را وارد کنید (نه `/api/sitemap.xml`)
   - چون شما در Apache/Nginx این route را به backend proxy کرده‌اید
   - Google باید آدرس عمومی frontend را ببیند
3. روی "Submit" کلیک کنید

### مرحله 3: بررسی
- Google معمولاً در عرض چند ساعت sitemap را پردازش می‌کند
- می‌توانید وضعیت را در "Sitemaps" ببینید

---

## 🔍 ثبت در Bing Webmaster Tools

### مرحله 1: ثبت سایت
1. به https://www.bing.com/webmasters بروید
2. روی "Add a site" کلیک کنید
3. URL سایت را وارد کنید: `https://zanburak.ir`
4. روش تایید را انتخاب کنید

### مرحله 2: Submit Sitemap
1. در Bing Webmaster Tools، به "Sitemaps" بروید
2. روی "Submit Sitemap" کلیک کنید
3. این URL را وارد کنید:
   ```
   https://zanburak.ir/sitemap.xml
   ```
   ⚠️ **مهم:** فقط `/sitemap.xml` را وارد کنید (نه `/api/sitemap.xml`)
   - چون شما در Apache/Nginx این route را به backend proxy کرده‌اید
   - Bing باید آدرس عمومی frontend را ببیند
4. روی "Submit" کلیک کنید

---

## ✅ تست Sitemap

### تست آنلاین:
1. **Google Search Console:** بعد از submit، وضعیت را بررسی کنید
2. **XML Sitemap Validator:** https://www.xml-sitemaps.com/validate-xml-sitemap.html
3. **Bing Webmaster Tools:** بعد از submit، وضعیت را بررسی کنید

### تست دستی:
```bash
# تست sitemap
curl https://zanburak.ir/sitemap.xml

# تست robots
curl https://zanburak.ir/robots.txt
```

---

## 📝 نکات مهم

1. **Sitemap باید در root باشد:** `https://zanburak.ir/sitemap.xml` (نه `/api/sitemap.xml`)
   - در Google Search Console و Bing Webmaster Tools: `https://zanburak.ir/sitemap.xml` وارد کنید
   - Apache/Nginx به صورت خودکار این را به backend proxy می‌کند

2. **Robots.txt باید در root باشد:** `https://zanburak.ir/robots.txt` (نه `/api/robots.txt`)
   - Robots.txt خودکار از root خوانده می‌شود
   - نیازی به ثبت در Google/Bing نیست

3. **Content-Type مهم است:**
   - Sitemap: `application/xml`
   - Robots: `text/plain`

4. **به‌روزرسانی خودکار:** چون داینامیک است، هر بار که محتوا تغییر می‌کند، sitemap به‌روز می‌شود

5. **Cache:** می‌توانید در frontend server cache کنید:
   ```nginx
   location = /sitemap.xml {
       proxy_cache sitemap_cache;
       proxy_cache_valid 200 1h;
       proxy_pass http://your-backend-url/api/sitemap.xml;
   }
   ```

---

## 🚨 مشکلات رایج

### مشکل 1: 404 Not Found
**علت:** Routes در frontend server تنظیم نشده
**راه حل:** مطابق راهنمای بالا، rewrite rules را اضافه کنید

### مشکل 2: Content-Type اشتباه
**علت:** Backend header را درست set نمی‌کند
**راه حل:** در `SeoController`، header را بررسی کنید

### مشکل 3: Google نمی‌خواند
**علت:** ممکن است robots.txt اجازه ندهد
**راه حل:** مطمئن شوید که `/sitemap.xml` در robots.txt disallow نیست

---

## 📊 مانیتورینگ

بعد از ثبت sitemap:
- **Google Search Console:** تعداد صفحات ایندکس شده را ببینید
- **Bing Webmaster Tools:** وضعیت ایندکس را بررسی کنید
- **Logs:** در server logs، درخواست‌های `/sitemap.xml` را بررسی کنید

---

## 🔄 به‌روزرسانی خودکار

Sitemap شما داینامیک است، پس:
- هر بار که دوره جدید اضافه می‌شود → در sitemap است
- هر بار که اپیزود جدید اضافه می‌شود → در sitemap است
- هر بار که سوال جدید اضافه می‌شود → در sitemap است

**نکته:** Google معمولاً هر چند روز یکبار sitemap را دوباره می‌خواند. می‌توانید در Google Search Console، "Request indexing" کنید.

