<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

final class SettingSeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->getStoreSettings() as $key => $value) {
            Setting::setValue($key, $value);
        }

        foreach ($this->getPolicySettings() as $key => $value) {
            Setting::setValue($key, $value);
        }
    }

    private function getStoreSettings(): array
    {
        return [
            'store_name' => 'Flower Shop',
            'store_description' => 'Flower Shop is an online flower shop.',
            'store_email' => 'contact@example.com',
            'store_phone' => '+14155552671',
            'store_street_address' => '123 Main St',
            'store_city' => 'New York City',
            'store_state' => 'NY',
            'store_postal_code' => '12345',
            'store_country_code' => 'US',

            // Storefront settings
            'storefront_header_layout' => 'classic',
            'storefront_header_sticky' => true,
            'storefront_header_compact_on_scroll' => true,
            'storefront_header_show_appearance_toggle' => true,
            'storefront_theme_color' => 'blue',
            'storefront_appearance' => 'light',
        ];
    }

    private function getPolicySettings(): array
    {
        return [
            'refund_policy' => $this->getRefundPolicy(),
            'privacy_policy' => $this->getPrivacyPolicy(),
            'terms_of_service' => $this->getTermsOfService(),
        ];
    }

    private function getRefundPolicy(): string
    {
        return <<<'HTML'
<p class="lead">We want you to be completely satisfied with your purchase. If you're not happy with your order, we're here to help.</p>

<h2>Returns</h2>
<p>You have <strong>30 days</strong> from the date of delivery to return most items for a full refund. Items must be:</p>
<ul>
<li>Unused and in the same condition that you received them</li>
<li>In the original packaging</li>
<li>Accompanied by the receipt or proof of purchase</li>
</ul>

<h2>Non-Returnable Items</h2>
<p>Certain items cannot be returned, including:</p>
<ul>
<li>Gift cards</li>
<li>Downloadable products</li>
<li>Perishable goods (flowers, plants, food items)</li>
<li>Personal care items</li>
<li>Items marked as final sale</li>
</ul>

<h2>Refunds</h2>
<p>Once we receive your returned item, we will inspect it and notify you of the approval or rejection of your refund. If approved, your refund will be processed within <strong>5-7 business days</strong> to your original payment method.</p>

<h2>Exchanges</h2>
<p>If you need to exchange an item for the same product in a different size or color, please contact us at <a href="mailto:contact@example.com">contact@example.com</a>. We'll help you process the exchange as quickly as possible.</p>

<h2>Shipping Costs</h2>
<p>Return shipping costs are the responsibility of the customer unless the return is due to our error (wrong item, defective product, etc.). In such cases, we will provide a prepaid shipping label.</p>

<h2>Questions?</h2>
<p>If you have any questions about returns or refunds, please don't hesitate to contact our customer service team at <a href="mailto:contact@example.com">contact@example.com</a>.</p>
HTML;
    }

    private function getPrivacyPolicy(): string
    {
        return <<<'HTML'
<p class="lead">This Privacy Policy describes how we collect, use, and share information about you when you use our website and services.</p>
<p><em>Last updated: January 1, 2026</em></p>

<h2>Information We Collect</h2>

<h3>Information You Provide</h3>
<p>We collect information you provide directly to us, such as when you:</p>
<ul>
<li>Create an account</li>
<li>Make a purchase</li>
<li>Subscribe to our newsletter</li>
<li>Contact customer support</li>
<li>Participate in surveys or promotions</li>
</ul>
<p>This information may include your name, email address, postal address, phone number, and payment information.</p>

<h3>Information We Collect Automatically</h3>
<p>When you visit our website, we automatically collect certain information, including:</p>
<ul>
<li>Device information (browser type, operating system)</li>
<li>IP address and location data</li>
<li>Pages visited and time spent on our site</li>
<li>Referring website or source</li>
</ul>

<h2>How We Use Your Information</h2>
<p>We use the information we collect to:</p>
<ul>
<li>Process and fulfill your orders</li>
<li>Send you order confirmations and updates</li>
<li>Respond to your comments and questions</li>
<li>Send promotional communications (with your consent)</li>
<li>Improve our website and services</li>
<li>Detect and prevent fraud</li>
</ul>

<h2>Information Sharing</h2>
<p>We do not sell your personal information. We may share your information with:</p>
<ul>
<li><strong>Service providers</strong> who help us operate our business (payment processors, shipping carriers)</li>
<li><strong>Legal authorities</strong> when required by law or to protect our rights</li>
<li><strong>Business partners</strong> with your consent</li>
</ul>

<h2>Your Rights</h2>
<p>You have the right to:</p>
<ul>
<li>Access and update your personal information</li>
<li>Request deletion of your data</li>
<li>Opt out of marketing communications</li>
<li>Request a copy of your data</li>
</ul>

<h2>Cookies</h2>
<p>We use cookies and similar technologies to enhance your experience, analyze trends, and administer the website. You can control cookies through your browser settings.</p>

<h2>Security</h2>
<p>We implement appropriate security measures to protect your personal information. However, no method of transmission over the Internet is 100% secure.</p>

<h2>Contact Us</h2>
<p>If you have questions about this Privacy Policy, please contact us at <a href="mailto:contact@example.com">contact@example.com</a>.</p>
HTML;
    }

    private function getTermsOfService(): string
    {
        return <<<'HTML'
<p class="lead">Please read these Terms of Service carefully before using our website. By accessing or using our service, you agree to be bound by these terms.</p>
<p><em>Last updated: January 1, 2026</em></p>

<h2>1. Acceptance of Terms</h2>
<p>By accessing and using this website, you accept and agree to be bound by these Terms of Service and our Privacy Policy. If you do not agree to these terms, please do not use our services.</p>

<h2>2. Use of Our Service</h2>
<p>You agree to use our service only for lawful purposes and in accordance with these terms. You agree not to:</p>
<ul>
<li>Use the service in any way that violates applicable laws or regulations</li>
<li>Attempt to gain unauthorized access to any part of the service</li>
<li>Interfere with or disrupt the service or servers</li>
<li>Use automated systems or software to extract data from the website</li>
<li>Impersonate any person or entity</li>
</ul>

<h2>3. Account Registration</h2>
<p>To access certain features, you may need to create an account. You are responsible for:</p>
<ul>
<li>Maintaining the confidentiality of your account credentials</li>
<li>All activities that occur under your account</li>
<li>Notifying us immediately of any unauthorized use</li>
</ul>

<h2>4. Products and Pricing</h2>
<p>We strive to display accurate product information and pricing. However, errors may occur. We reserve the right to:</p>
<ul>
<li>Correct any errors in pricing or product descriptions</li>
<li>Cancel orders placed with incorrect pricing</li>
<li>Limit quantities available for purchase</li>
<li>Refuse service to anyone for any reason</li>
</ul>

<h2>5. Payment Terms</h2>
<p>By placing an order, you agree to pay the full amount, including applicable taxes and shipping fees. We accept major credit cards and other payment methods as displayed at checkout.</p>

<h2>6. Shipping and Delivery</h2>
<p>Shipping times are estimates and not guaranteed. We are not responsible for delays caused by carriers, customs, or circumstances beyond our control.</p>

<h2>7. Intellectual Property</h2>
<p>All content on this website, including text, graphics, logos, images, and software, is our property or the property of our licensors and is protected by intellectual property laws.</p>

<h2>8. Limitation of Liability</h2>
<p>To the fullest extent permitted by law, we shall not be liable for any indirect, incidental, special, consequential, or punitive damages arising from your use of our services.</p>

<h2>9. Changes to Terms</h2>
<p>We may update these terms from time to time. We will notify you of any changes by posting the new terms on this page. Your continued use of the service after changes constitutes acceptance of the new terms.</p>

<h2>10. Contact Information</h2>
<p>If you have any questions about these Terms of Service, please contact us at <a href="mailto:contact@example.com">contact@example.com</a>.</p>
HTML;
    }
}
