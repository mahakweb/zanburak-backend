# خلاصه پیاده‌سازی Meta Keywords برای Course

## ✅ تغییرات انجام شده

### 1. Validation در `store` و `update`

**محدودیت‌ها:**
- ✅ حداقل 3 کلمه کلیدی
- ✅ حداکثر 10 کلمه کلیدی
- ✅ کلمات با کاما جدا می‌شوند
- ✅ کلمات خالی حذف می‌شوند
- ✅ فیلد اختیاری است (nullable)

**کد Validation:**
```php
'meta_keywords' => [
    'nullable',
    'string',
    function ($attribute, $value, $fail) {
        if ($value) {
            $keywords = array_filter(array_map('trim', explode(',', $value)));
            $count = count($keywords);
            
            if ($count < 3) {
                $fail('کلمات کلیدی باید حداقل 3 کلمه باشد.');
            }
            
            if ($count > 10) {
                $fail('کلمات کلیدی نباید بیشتر از 10 کلمه باشد.');
            }
            
            // Check for empty keywords
            foreach ($keywords as $keyword) {
                if (empty($keyword)) {
                    $fail('کلمات کلیدی نمی‌تواند خالی باشد.');
                    break;
                }
            }
        }
    },
],
```

### 2. Format کردن Keywords

**در `store`:**
```php
// Clean and format meta_keywords
if (isset($validData['meta_keywords']) && $validData['meta_keywords']) {
    $keywords = array_filter(array_map('trim', explode(',', $validData['meta_keywords'])));
    $validData['meta_keywords'] = implode(', ', $keywords);
}
```

**در `update`:**
```php
// Clean and format meta_keywords
if (isset($validData['meta_keywords']) && $validData['meta_keywords']) {
    $keywords = array_filter(array_map('trim', explode(',', $validData['meta_keywords'])));
    $validData['meta_keywords'] = implode(', ', $keywords);
} else {
    $validData['meta_keywords'] = null;
}
```

### 3. اضافه شدن به Response ها

**در متد `edit`:**
```php
'meta_keywords' => $course->meta_keywords,
```

**در متد `baseDetails`:**
```php
'meta_keywords' => $course->meta_keywords,
```

---

## 📝 نحوه استفاده

### مثال Request برای ایجاد دوره:

```json
{
    "title": "آموزش Laravel",
    "english_title": "Laravel Tutorial",
    "short_description": "...",
    "description": "...",
    "meta_keywords": "آموزش Laravel, آموزش PHP, فریمورک لاراول, Laravel Framework, آموزش Backend",
    "status_id": 1,
    "level_id": 1,
    "type": "free",
    "categories": [1, 2],
    "publish": true,
    "price": 0
}
```

### مثال Response:

```json
{
    "message": "Success",
    "course": {
        "id": 1,
        "title": "آموزش Laravel",
        "meta_keywords": "آموزش Laravel, آموزش PHP, فریمورک لاراول, Laravel Framework, آموزش Backend",
        ...
    }
}
```

---

## ⚠️ خطاهای Validation

### اگر کمتر از 3 کلمه باشد:
```json
{
    "message": "Validation error!",
    "errors": {
        "meta_keywords": ["کلمات کلیدی باید حداقل 3 کلمه باشد."]
    }
}
```

### اگر بیشتر از 10 کلمه باشد:
```json
{
    "message": "Validation error!",
    "errors": {
        "meta_keywords": ["کلمات کلیدی نباید بیشتر از 10 کلمه باشد."]
    }
}
```

### اگر کلمه خالی باشد:
```json
{
    "message": "Validation error!",
    "errors": {
        "meta_keywords": ["کلمات کلیدی نمی‌تواند خالی باشد."]
    }
}
```

---

## 🔄 Format خودکار

Keywords به صورت خودکار format می‌شوند:

**ورودی:**
```
"آموزش Laravel,آموزش PHP,  فریمورک لاراول  ,Laravel Framework"
```

**خروجی (ذخیره شده):**
```
"آموزش Laravel, آموزش PHP, فریمورک لاراول, Laravel Framework"
```

**نکته:** فاصله‌های اضافی حذف می‌شوند و بعد از هر کاما یک فاصله اضافه می‌شود.

---

## 📋 چک‌لیست

- [x] Validation در `store` اضافه شد
- [x] Validation در `update` اضافه شد
- [x] Format کردن keywords در `store`
- [x] Format کردن keywords در `update`
- [x] اضافه شدن به response در `edit`
- [x] اضافه شدن به response در `baseDetails`
- [ ] تست در Frontend (کاربر باید انجام دهد)

---

## 🎯 مراحل بعدی

برای Episode، Question و Path هم باید همین کار را انجام دهید:

1. **EpisodeController** (Admin)
2. **QuestionController** (Admin) - اگر دارید
3. **PathController** (Admin)

همان validation و format کردن را اضافه کنید.

---

## 💡 نکات مهم

1. **Keywords اختیاری است:** اگر ارسال نشود، `null` ذخیره می‌شود
2. **Format خودکار:** Keywords به صورت خودکار format می‌شوند
3. **Validation در Backend:** تمام محدودیت‌ها در backend اعمال می‌شود
4. **Frontend:** باید validation در frontend هم اضافه شود (اختیاری اما توصیه می‌شود)

---

**تمام تغییرات برای Course انجام شد!** ✅

