# 📦 Laravel Ubill SMS Integration

[![Latest Version on Packagist](https://img.shields.io/packagist/v/class-atlas/laravel-usms.svg?style=flat-square)](https://packagist.org/packages/class-atlas/laravel-usms)
[![Total Downloads](https://img.shields.io/packagist/dt/class-atlas/laravel-usms.svg?style=flat-square)](https://packagist.org/packages/class-atlas/laravel-usms)

This Laravel package provides a simple and elegant integration with [Ubill](https://ubill.ge)'s SMS service, allowing your Laravel application to send text messages directly through Ubill's API.

Whether you're sending authentication codes, notifications, or alerts — this package helps you connect your app with Ubill's reliable SMS infrastructure in minutes.

---

## 📌 Features

- 📤 SMS Sending
- 🛰️ Event Dispatching for Sent SMS
- 📬 Delivery Reports
- 💰 Get SMS Balance
- 🏷️ Brand Name Listing
- 📝 Brand Name Registration

---

## 🧱 Installation

Install the package via Composer:

```bash
composer require class-atlas/laravel-usms
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
public function toUSms(object $notifiable): MessageData
{
    return MessageData::from([
        'brandId' => 123,
        'numbers' => ['+9955XXXXXXX'],
        'text' => 'Hello from Ubill!',
        'stopList' => false
    ]);
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
### 📡 USmsWasSent Event

A event, `USmsWasSent`, is now dispatched whenever a notification is sent using the `HasUsmsChannel`.  
This event receives a `USmsWasSentData` object containing:

- `notificationId`: Optional identifier that can be defined in your notification class.
- `sendSmsData`: The raw data returned by the Ubill SMS API.

This allows you to track delivery statuses or coordinate actions across multiple notification channels.

**Example:**  
If you're using both the `database` and `USms` channels in your notification, defining a shared `notificationId`
and pass to MessageData as a 5th parameter `notificationId`, it will allow you to correlate the records and update delivery statuses later based on the event.

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
