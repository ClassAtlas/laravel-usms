
# 📦 Laravel Ubill SMS Integration

[![Latest Version on Packagist](https://img.shields.io/packagist/v/kirkita/laravel-usms.svg?style=flat-square)](https://packagist.org/packages/kirkita/laravel-usms)
[![Total Downloads](https://img.shields.io/packagist/dt/kirkita/laravel-usms.svg?style=flat-square)](https://packagist.org/packages/kirkita/laravel-usms)

This Laravel package provides a simple and elegant integration with [Ubill](https://ubill.ge)'s SMS service, allowing your Laravel application to send text messages directly through Ubill's API.

Whether you're sending authentication codes, notifications, or alerts — this package helps you connect your app with Ubill's reliable SMS infrastructure in minutes.

---

## 📌 Features

- 📤 SMS Sending
- 📬 Delivery Reports
- 💰 Get SMS Balance
- 🏷️ Brand Name Listing
- 📝 Brand Name Registration

---

## 🧱 Installation

Install the package via Composer:

```bash
composer require kirkita/laravel-usms
```

---

## ⚙️ Configuration

Add the following to your `.env` file:

```env
UBILL_API_URL=https://api.ubill.dev
UBILL_SMS_API_KEY=your_api_key
UBILL_SENDING_DISABLED=false
```

---

## 📤 SMS Sending

### ✅ Direct Send

You can send a message directly via the `sendSms` method:

```php
USms::sendSms(
    brandId: int,
    numbers: array,
    text: string,
    stopList: bool
): SendSmsData;
```

- `brandId`: The ID of the brand name you’re sending from
- `numbers`: Array of recipient phone numbers. Phone number should include country code.
- `text`: The message content
- `stopList`: Whether to apply the stop-list filter (true/false)

---

### 🔔 Using Laravel Notifications

To send SMS through Laravel’s notification system:

1. Implement the `HasUsmsChannel` interface on your Notification class.
2. Define the `toUSms` method:

```php
public function toUSms(object $notifiable): array
{
    return [
        'brandID' => int,
        'numbers' => [],
        'text' => '',
        'stopList' => false,
    ];
}
```

3. Add the channel to the `via()` method:

```php
public function via($notifiable)
{
    return [ClassAtlas\USms\Channels\USmsChannel::class];
}
```

---

## 📬 Delivery Reports

You can retrieve the delivery status of a sent SMS using its ID:

```php
USms::deliveryReport(smsId: int): ReportData;
```

---

## 💰 Get SMS Balance

To check the remaining balance of your Ubill account:

```php
USms::balance(): SmsBalanceData;
```

---

## 🏷️ Brand Name Listing

To get a list of all your registered brand names:

```php
USms::listBrandName(): BrandNameData;
```

---

## 📝 Brand Name Registration

You can register a new brand name using:

```php
USms::createBrandName(brandName: string): BrandNameCreateData;
```


