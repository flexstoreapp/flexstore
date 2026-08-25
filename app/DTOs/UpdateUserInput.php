<?php

declare(strict_types=1);

namespace App\DTOs;

use SensitiveParameter;

final readonly class UpdateUserInput
{
    /**
     * @param  list<int>|null  $roleIds
     * @param  array<string, true>  $provided
     */
    public function __construct(
        public ?string $name,
        public ?string $urlHandle,
        public ?string $email,
        #[SensitiveParameter] public ?string $password,
        public ?string $emailVerifiedAt,
        public ?string $appearance,
        public ?string $adminThemeColor,
        public ?array $roleIds,
        public array $provided,
    ) {
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        $provided = [];
        foreach (['name', 'url_handle', 'email', 'password', 'email_verified_at', 'appearance', 'admin_theme_color', 'roles'] as $key) {
            if (array_key_exists($key, $data)) {
                $provided[$key] = true;
            }
        }

        $roles = null;
        if (isset($data['roles']) && is_array($data['roles'])) {
            $roles = array_values(array_map(intval(...), $data['roles']));
        }

        return new self(
            name: isset($data['name']) ? (string) $data['name'] : null,
            urlHandle: isset($data['url_handle']) ? (string) $data['url_handle'] : null,
            email: isset($data['email']) ? (string) $data['email'] : null,
            password: isset($data['password']) && $data['password'] !== '' ? (string) $data['password'] : null,
            emailVerifiedAt: isset($data['email_verified_at']) ? (string) $data['email_verified_at'] : null,
            appearance: isset($data['appearance']) ? (string) $data['appearance'] : null,
            adminThemeColor: isset($data['admin_theme_color']) ? (string) $data['admin_theme_color'] : null,
            roleIds: $roles,
            provided: $provided,
        );
    }

    public function has(string $field): bool
    {
        return isset($this->provided[$field]);
    }
}
