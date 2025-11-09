# راهنمای Meta Keywords

## 📋 سوال 2: Meta Keywords - String یا JSON؟

### پاسخ: String با کاما (توصیه می‌شود) ✅

**دلایل:**
1. **ساده‌تر:** در فرم‌ها راحت‌تر است
2. **سریع‌تر:** نیازی به JSON encode/decode نیست
3. **قابل خواندن:** در دیتابیس قابل خواندن است
4. **سازگار با Frontend:** در JavaScript راحت split می‌شود

**فرمت:**
```
آموزش Laravel, آموزش PHP, فریمورک لاراول, Laravel Framework
```

---

## 🗄️ ساختار دیتابیس

Migration قبلاً ایجاد شده است. فیلد `meta_keywords` به صورت `TEXT` است:

```php
$table->text('meta_keywords')->nullable();
```

**نکته:** نیازی به cast کردن به JSON نیست. به صورت string ذخیره می‌شود.

---

## 📝 استفاده در فرم‌های ایجاد/ویرایش

### در Backend (Laravel)

#### مثال: فرم ایجاد دوره

```php
// در Controller
public function store(Request $request)
{
    $validated = $request->validate([
        'title' => 'required|string',
        'description' => 'required|string',
        'meta_keywords' => 'nullable|string', // String با کاما
        // ... سایر فیلدها
    ]);

    $course = Course::create([
        'title' => $validated['title'],
        'description' => $validated['description'],
        'meta_keywords' => $validated['meta_keywords'], // ذخیره به صورت string
        // ...
    ]);
}
```

#### مثال: فرم ویرایش دوره

```php
public function update(Request $request, Course $course)
{
    $validated = $request->validate([
        'title' => 'required|string',
        'description' => 'required|string',
        'meta_keywords' => 'nullable|string',
        // ...
    ]);

    $course->update([
        'title' => $validated['title'],
        'description' => $validated['description'],
        'meta_keywords' => $validated['meta_keywords'],
        // ...
    ]);
}
```

### در Frontend (Vue)

#### مثال: Input Field

```vue
<template>
  <div>
    <label>کلمات کلیدی (با کاما جدا کنید):</label>
    <input 
      v-model="metaKeywords" 
      type="text" 
      placeholder="آموزش Laravel, آموزش PHP, فریمورک لاراول"
    />
    <small>کلمات را با کاما (,) جدا کنید</small>
  </div>
</template>

<script>
export default {
  data() {
    return {
      metaKeywords: '' // یا از API بیاید
    }
  },
  methods: {
    async saveCourse() {
      await axios.post('/api/course', {
        title: this.title,
        description: this.description,
        meta_keywords: this.metaKeywords, // ارسال به صورت string
        // ...
      })
    }
  }
}
</script>
```

---

## 🔄 تبدیل در Frontend

در Frontend، keywords را از string به array تبدیل می‌کنیم:

```javascript
// در CourseShow.vue (قبلاً اضافه شده)
const dynamicKeywords = course.value.meta_keywords 
    ? course.value.meta_keywords.split(',').map(k => k.trim()).filter(k => k)
    : [];
```

**توضیح:**
- `split(',')` - تبدیل string به array با کاما
- `map(k => k.trim())` - حذف فاصله‌های اضافی
- `filter(k => k)` - حذف مقادیر خالی

---

## 📤 API Response

### بررسی کنید که meta_keywords در API response ها باشد:

#### CourseController.php

در متد `getCourse`، course object کامل return می‌شود که شامل `meta_keywords` است:

```php
// در getCourse - course object کامل return می‌شود
return response()->json([
    'course' => $course, // شامل meta_keywords
    // ...
]);
```

#### EpisodeController.php

در متد `getEpisode`، episode object کامل return می‌شود:

```php
return response()->json([
    'episode' => $episode, // شامل meta_keywords
    // ...
]);
```

#### IndexController.php (getPath)

Path object کامل return می‌شود:

```php
return response()->json([
    'path' => $path, // شامل meta_keywords
    // ...
]);
```

#### DiscussController.php

باید بررسی کنید که question object شامل `meta_keywords` باشد.

---

## ✅ چک‌لیست پیاده‌سازی

### Backend:
- [x] Migration ایجاد شد
- [x] Models به‌روزرسانی شدند (fillable)
- [x] API response ها شامل meta_keywords هستند (خودکار - چون object کامل return می‌شود)
- [ ] در فرم‌های ایجاد/ویرایش (Admin Panel) فیلد اضافه شود
- [ ] Validation اضافه شود

### Frontend:
- [x] Keywords داینامیک در صفحات اضافه شد
- [x] تبدیل string به array پیاده‌سازی شد
- [ ] در فرم‌های ایجاد/ویرایش (اگر دارید) فیلد اضافه شود

---

## 📝 مثال کامل

### ذخیره در دیتابیس:
```
meta_keywords = "آموزش Laravel, آموزش PHP, فریمورک لاراول, Laravel Framework"
```

### خواندن در Frontend:
```javascript
// از API
const course = await axios.get('/api/course/laravel-basics');
// course.data.course.meta_keywords = "آموزش Laravel, آموزش PHP, ..."

// تبدیل به array
const keywords = course.data.course.meta_keywords
    .split(',')
    .map(k => k.trim())
    .filter(k => k);
// keywords = ["آموزش Laravel", "آموزش PHP", "فریمورک لاراول", "Laravel Framework"]
```

### استفاده در SEO:
```javascript
useSEO({
    keywords: [
        ...staticKeywords,
        ...dynamicKeywords // از meta_keywords
    ]
});
```

---

## 🎯 بهترین روش‌ها

1. **حداکثر 10-15 کلمه کلیدی:** بیش از حد نباشد
2. **کلمات مرتبط:** فقط کلمات مرتبط با محتوا
3. **زبان فارسی و انگلیسی:** می‌توانید هر دو را استفاده کنید
4. **بدون تکرار:** کلمات تکراری نباشد
5. **کلمات کلیدی اصلی:** کلمات مهم‌تر را اول بگذارید

**مثال خوب:**
```
آموزش Laravel, آموزش PHP, فریمورک لاراول, Laravel Framework, آموزش Backend
```

**مثال بد:**
```
Laravel, Laravel, Laravel, آموزش, آموزش, آموزش, PHP, PHP, PHP
```

---

## 🔧 اگر می‌خواهید JSON استفاده کنید

اگر واقعاً می‌خواهید JSON استفاده کنید:

### Migration:
```php
$table->json('meta_keywords')->nullable();
```

### Model:
```php
protected $casts = [
    'meta_keywords' => 'array',
];
```

### استفاده:
```php
// ذخیره
$course->meta_keywords = ['آموزش Laravel', 'آموزش PHP'];

// خواندن
$keywords = $course->meta_keywords; // ['آموزش Laravel', 'آموزش PHP']
```

**اما:** این روش پیچیده‌تر است و در فرم‌ها باید JSON string بسازید.

---

## ✅ نتیجه‌گیری

**توصیه:** از **String با کاما** استفاده کنید. ساده‌تر، سریع‌تر و قابل خواندن‌تر است.

تمام کدهای Frontend قبلاً برای string با کاما نوشته شده‌اند و کار می‌کنند.

