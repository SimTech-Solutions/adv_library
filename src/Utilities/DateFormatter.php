<?php

declare(strict_types=1);

namespace AdvClientAPI\Utilities;

use DateTime;
use DateTimeImmutable;
use DateTimeZone;

/**
 * Date and time formatting utilities
 */
class DateFormatter
{
    /**
     * Format date for DOS (Date of Service) field
     * Converts to ISO 8601 format: YYYY-MM-DD
     *
     * @param string $date Date string in various formats
     * @return string ISO 8601 formatted date
     */
    public static function formatDosDate(string $date): string
    {
        try {
            $dt = new DateTime($date);
            return $dt->format('Y-m-d');
        } catch (\Exception $e) {
            throw new \InvalidArgumentException("Invalid date format: {$date}", 0, $e);
        }
    }

    /**
     * Get current datetime in ISO 8601 format with timezone
     * Example: 2024-02-20T14:30:45Z
     *
     * @return string
     */
    public static function getCurrentIso8601($dos_raw): string
    {
        if ($dos_raw instanceof DateTime) {

            return $dos_raw->format('Y-m-d\TH:i:s.v\Z');
        } // Case 2: If it's a string
        if (is_string($dos_raw) && !empty($dos_raw)) {
            try {
                // Replace space with T, strip trailing Z 
                $dos_str = str_replace(" ", "T", rtrim($dos_raw, "Z"));
                $parsed_dt = new DateTime($dos_str);
                return $parsed_dt->format('Y-m-d\TH:i:s.v\Z');
            } catch (\Exception $e) {
                // If parsing fails, enforce T and Z manually 
                $dos_str = str_replace(" ", "T", $dos_raw);
                if (!str_ends_with($dos_str, "Z")) {
                    return  str_replace (" ","","$dos_str Z");
                }
                return $dos_str;
            }
        } // Case 3: Fallback 
        return $dos_raw ? \strval($dos_raw) : "";
        // $dt = new DateTimeImmutable('now', new DateTimeZone('UTC'));
        // return $dt->format(DateTime::ATOM);
    }

    /**
     * Get current datetime plus specified seconds
     * Used for token expiration calculation
     *
     * @param int $seconds
     * @return int Unix timestamp
     */
    public static function getExpirationTimestamp(int $seconds = 3600): int
    {
        return time() + $seconds;
    }

    /**
     * Format timestamp as ISO 8601
     *
     * @param int $timestamp Unix timestamp
     * @return string
     */
    public static function formatTimestamp(int $timestamp): string
    {
        $dt = new DateTimeImmutable("@{$timestamp}", new DateTimeZone('UTC'));
        return $dt->format(DateTime::ATOM);
    }

    /**
     * Check if timestamp is expired
     *
     * @param int $expiresAt Unix timestamp
     * @return bool
     */
    public static function isExpired(int $expiresAt): bool
    {
        return time() > $expiresAt;
    }

    /**
     * Get seconds until expiration
     *
     * @param int $expiresAt Unix timestamp
     * @return int Seconds remaining (or negative if expired)
     */
    public static function getSecondsUntilExpiration(int $expiresAt): int
    {
        return $expiresAt - time();
    }
}
