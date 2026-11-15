<?php

/*
 * Copyright (c) 2026 Besnovatyj. Licensed under the MIT License.
 */

declare(strict_types=1);

namespace Besnovatyj\Shortcode\definitions;

use Besnovatyj\Shortcode\entities\Shortcode;
use InvalidArgumentException;
use yii\base\InvalidConfigException;

/** Полное неизменяемое определение; значение всегда является конечным результатом. */
final readonly class ShortcodeDefinition
{
    /** Создаёт и проверяет определение независимо от источника регистрации. */
    public function __construct(
        public string $shortcode,
        public string $type,
        public string $replacement,
        public string $description = '',
        public string $example = '',
        public ReverseMode $reverseMode = ReverseMode::None,
        public ?int $id = null,
    ) {
        $pattern = $type === Shortcode::TYPE_TEXT ? '/^%[A-Za-z0-9_]+%$/D' : '/^[A-Za-z0-9_]+$/D';
        if (!in_array($type, [Shortcode::TYPE_TEXT, Shortcode::TYPE_WIDGET], true)
            || preg_match($pattern, $shortcode) !== 1) {
            throw new InvalidArgumentException("Некорректное имя или тип шорткода: {$shortcode}.");
        }
        if ($type === Shortcode::TYPE_TEXT && preg_match('/%[A-Za-z0-9_]+%/', $replacement)) {
            throw new InvalidArgumentException("Значение {$shortcode} не должно содержать вложенные шорткоды.");
        }
        if ($reverseMode !== ReverseMode::None) {
            if ($type !== Shortcode::TYPE_TEXT) {
                throw new InvalidArgumentException('Обратное преобразование доступно только текстовым шорткодам.');
            }
            $this->reverseKey();
        }
    }

    /** Ключ сравнения HTTP(S)-префиксов: регистр хоста и стандартные порты несущественны. */
    public function reverseKey(): string
    {
        $parts = parse_url($this->replacement);
        if ($parts === false || !isset($parts['scheme'], $parts['host'])
            || !in_array(strtolower($parts['scheme']), ['http', 'https'], true)
            || preg_match('/[\x00-\x20\x7f\\\\]/', $this->replacement)
            || isset($parts['user']) || isset($parts['pass']) || isset($parts['query']) || isset($parts['fragment'])
            || preg_match('/[^\x21-\x7e]/', $parts['host'])
            || str_contains($parts['host'], '%')) {
            throw new InvalidArgumentException("Для {$this->shortcode} нужен абсолютный HTTP(S)-префикс без credentials, query и fragment.");
        }
        $host = strtolower($parts['host']);
        $ip = trim($host, '[]');
        if (str_starts_with($host, '[')) {
            if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) === false || inet_ntop(inet_pton($ip)) !== $ip) {
                throw new InvalidArgumentException('Используйте канонический IPv6-хост.');
            }
        } elseif (preg_match('/^[0-9.]+$/D', $host)) {
            if (filter_var($host, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) === false) {
                throw new InvalidArgumentException('Используйте канонический IPv4-хост.');
            }
        } elseif (preg_match('/(?:^|\.)(?:[0-9]+|0x[0-9a-f]+)\.?$/i', $host)
            || filter_var($host, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME) === false) {
            throw new InvalidArgumentException('Некорректное имя хоста.');
        }
        $authority = preg_replace('~^https?://([^/]+).*$~i', '$1', $this->replacement);
        if (preg_match('/:(0[0-9]+)$/D', $authority)) {
            throw new InvalidArgumentException('Порт не должен содержать ведущие нули.');
        }
        $scheme = strtolower($parts['scheme']);
        $port = $parts['port'] ?? ($scheme === 'https' ? 443 : 80);
        $expectedAuthority = $host . (isset($parts['port']) && $parts['port'] !== ($scheme === 'https' ? 443 : 80) ? ':' . $parts['port'] : '');
        $normalizedAuthority = preg_replace($scheme === 'https' ? '/:443$/D' : '/:80$/D', '', strtolower($authority));
        if ($normalizedAuthority !== $expectedAuthority) {
            throw new InvalidArgumentException('Используйте каноническое имя хоста и порт.');
        }
        $path = $parts['path'] ?? '';
        // WHATWG URL нормализует dot-сегменты. Не допускаем неоднозначности с PHP parse_url.
        if (preg_match('~(?:^|/)(?:\.|%2e){1,2}(?:/|$)~i', $path)) {
            throw new InvalidArgumentException('Префикс URL не должен содержать dot-сегменты.');
        }
        return $scheme . '://' . strtolower($parts['host']) . ':' . $port . $path;
    }

    /** Восстанавливает определение из строки каталога БД. */
    public static function fromRow(array $row): self
    {
        if (!array_key_exists('reverse_mode', $row)) {
            throw new InvalidConfigException(
                'В данных каталога шорткодов отсутствует reverse_mode. '
                . 'Примените миграцию Shortcode m261115_000001_add_shortcode_reverse_mode через modman '
                . 'и обновите кэш каталога.',
            );
        }
        return new self(
            shortcode: (string)$row['shortcode'],
            type: (string)$row['type'],
            replacement: (string)$row['replacement'],
            description: (string)($row['description'] ?? ''),
            example: (string)($row['example'] ?? ''),
            reverseMode: ReverseMode::from((string)$row['reverse_mode']),
            id: (int)$row['id'],
        );
    }
}
