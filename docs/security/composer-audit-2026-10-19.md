# Composer audit — 2026-10-19

`composer audit` di commit `f9a03b9` melaporkan **50 advisori di 13 paket**. Semuanya paket transitif;
semuanya tertutup dengan `composer update -W` dalam constraint `composer.json` yang ada
(tidak ada constraint yang dinaikkan). Setelah update: `No security vulnerability advisories found.`

## Versi yang naik (paket terdampak)

| Paket | Sebelum | Sesudah |
|---|---|---|
| guzzlehttp/guzzle | 7.10.0 | 7.16.0 |
| guzzlehttp/psr7 | 2.8.0 | 2.13.2 |
| laravel/framework | v12.49.0 | v12.70.0 |
| league/commonmark | 2.8.0 | 2.10.4 |
| league/flysystem | 3.31.0 | 3.36.0 |
| phpseclib/phpseclib | 3.0.49 | 3.0.57 |
| symfony/http-foundation | v7.4.5 | v7.4.21 |
| symfony/http-kernel | v7.4.5 | v7.4.21 |
| symfony/mailer | v7.4.4 | v7.4.19 |
| symfony/mime | v7.4.5 | v7.4.19 |
| symfony/polyfill-intl-idn | v1.33.0 | v1.43.0 |
| symfony/routing | v7.4.4 | v7.4.21 |
| symfony/yaml | v7.4.1 | v7.4.21 |

Dependensi lain ikut naik minor/patch karena `-W` (lihat diff `composer.lock`).

## Advisori yang ditutup, menurut keparahan

### High (15)

| Paket | CVE | Judul |
|---|---|---|
| guzzlehttp/guzzle | CVE-2026-69246 | Guzzle: Noncanonical host can bypass host-based checks |
| laravel/framework | — | Laravel Framework: CRLF injection in default email rule |
| league/commonmark | — | league/commonmark: Denial of service via crafted code fences, reference links, and emphasis delimiters |
| league/commonmark | — | league/commonmark: Denial of service via distinctly-named attributes in the Attributes extension |
| league/commonmark | — | league/commonmark:  Denial of service via duplicate footnote definitions |
| league/commonmark | — | league/commonmark: Denial of service via adjacent inline attribute blocks |
| league/commonmark | — | league/commonmark: Denial of service in the SmartPunct and Attributes extensions |
| league/commonmark | — | league/commonmark: Denial of service via colliding heading slugs |
| league/commonmark | — | league/commonmark: Quadratic-time denial of service in the GitHub Flavored Markdown Table extension block-start scan |
| league/commonmark | — | league/commonmark XSS: `on*` event-handler filter in `AttributesExtension` bypassed with a U+000C form feed |
| league/commonmark | CVE-2026-71488 | league/commonmark: Quadratic-time denial of service when parsing crafted Markdown |
| phpseclib/phpseclib | CVE-2026-32935 | phpseclib's AES-CBC unpadding susceptible to padding oracle timing attack |
| phpseclib/phpseclib | CVE-2026-44167 | phpseclib has a CVE-2024-27355 mitigation bypass — OID amplification DoS in ASN1::decodeOID() |
| symfony/http-kernel | CVE-2026-45075 | CVE-2026-45075: HEAD Request Bypasses methods: ['GET'] Filter in #[IsGranted] / #[IsSignatureValid] / #[IsCsrfTokenValid] |
| symfony/mime | CVE-2026-45067 | CVE-2026-45067: Email Header / SMTP Command Injection via CRLF in Symfony\Component\Mime\Address |

### Medium (25)

| Paket | CVE | Judul |
|---|---|---|
| guzzlehttp/guzzle | CVE-2026-55568 | Silent HTTPS proxy downgrade to cleartext |
| guzzlehttp/guzzle | CVE-2026-55767 | Dot-only cookie domains match all hosts |
| guzzlehttp/guzzle | CVE-2026-59883 | Guzzle: Cookie Disclosure and Injection via IP-Address Domains |
| guzzlehttp/guzzle | CVE-2026-67339 | Guzzle: Proxy-Authorization headers can be sent to origin servers |
| guzzlehttp/guzzle | CVE-2026-67353 | Guzzle: Unbounded response cookies risk denial of service |
| guzzlehttp/guzzle | CVE-2026-67354 | Guzzle: URI fragments disclosed in redirect Referer headers |
| guzzlehttp/guzzle | CVE-2026-67355 | Guzzle: Host-only cookie scope is not preserved |
| guzzlehttp/guzzle | CVE-2026-69245 | Guzzle: Noncanonical cookie domain keeps subdomain scope |
| guzzlehttp/psr7 | CVE-2026-48998 | Host confusion via authority reinterpretation |
| guzzlehttp/psr7 | CVE-2026-49214 | CRLF injection via URI host component |
| guzzlehttp/psr7 | CVE-2026-55766 | CRLF injection in HTTP start-line serialization |
| guzzlehttp/psr7 | CVE-2026-59882 | guzzlehttp/psr7: Host Confusion via Weak URI Host Validation |
| laravel/framework | — | Laravel Framework: Temporary Signed URL Path Confusion |
| league/commonmark | — | league/commonmark: DisallowedRawHtml bypassed when a disallowed tag name ends the raw-HTML literal |
| league/commonmark | — | league/commonmark: Denial of service via deeply nested XML output |
| league/commonmark | CVE-2026-30838 | CommonMark has DisallowedRawHtml extension bypass via whitespace in HTML tag names |
| league/commonmark | CVE-2026-33347 | league/commonmark has an embed extension allowed_domains bypass |
| league/commonmark | CVE-2026-71478 | league/commonmark: AttributesExtension href/src unsafe-link filter bypass via embedded control bytes |
| phpseclib/phpseclib | CVE-2026-55599 | phpseclib: X.509 certificate validation sends attacker-controlled outbound requests (server-side request forgery) via Authority Information Access |
| phpseclib/phpseclib | CVE-2026-84308 | phpseclib — non-constant-time X25519 scalar multiplication permits full private-key recovery |
| symfony/http-foundation | CVE-2026-48736 | CVE-2026-48736: IpUtils::PRIVATE_SUBNETS Omits IPv6 Transition Forms (6to4, NAT64, Teredo, IPv4-compatible): SSRF Bypass in NoPrivateNetworkHttpClient |
| symfony/mailer | CVE-2026-45068 | CVE-2026-45068: Argument Injection in SendmailTransport via Dash-Prefixed Recipient Address |
| symfony/mime | CVE-2026-45070 | CVE-2026-45070: Email Header Injection via Non-Token Characters in Mime Parameter Names |
| symfony/routing | CVE-2026-45065 | CVE-2026-45065: UrlGenerator Route-Requirement Bypass via Unanchored Regex Alternation → Off-Site //host URL Injection |
| symfony/routing | CVE-2026-48784 | CVE-2026-48784: UrlGenerator Dot-Segment Encoding Skips Every Other Chained `../` or `./` → Generated URL Collapses Off-Route Under RFC 3986 Normalization |

### Low (7)

| Paket | CVE | Judul |
|---|---|---|
| laravel/framework | CVE-2026-102279 | Laravel: XSS in Debug Page Information |
| league/flysystem | CVE-2026-102601 | Flysystem: WhitespacePathNormalizer's control-character (CorruptedPathDetected) check is bypassed by malformed UTF-8 in the path, affecting every adapter |
| phpseclib/phpseclib | CVE-2026-40194 | phpseclib has a variable-time HMAC comparison in SSH2::get_binary_packet() using != instead of hash_equals() |
| symfony/polyfill-intl-idn | CVE-2026-46644 | CVE-2026-46644: symfony/polyfill-intl-idn accepts xn-- labels whose Punycode payload decodes to ASCII-only: insecure equivalence |
| symfony/yaml | CVE-2026-45133 | CVE-2026-45133: YAML Parser Stack Exhaustion via Unbounded Recursion in Nested Blocks, Sequences, and Mappings |
| symfony/yaml | CVE-2026-45304 | CVE-2026-45304: YAML Parser Exponential Memory Allocation via Recursive Collection-Alias Expansion ("Billion Laughs") |
| symfony/yaml | CVE-2026-45305 | CVE-2026-45305: YAML Parser ReDoS via Catastrophic Backtracking in Parser::cleanup() Regex |

### Belum dinilai (3)

| Paket | CVE | Judul |
|---|---|---|
| laravel/framework | CVE-2026-48019 | Laravel CRLF injection in default email rule |
| symfony/http-foundation | CVE-2026-108082 | CVE-2026-108082: ReDoS in HeaderUtils::split() Reachable Through the Cache-Control Request Header |
| symfony/yaml | CVE-2026-109413 | CVE-2026-109413: Unbounded Recursion in the YAML Inline Lexer Lets a Single Document Crash the Process |

## Advisori yang tersisa

Tidak ada. Tidak ada advisori yang membutuhkan breaking change (mis. Laravel 13 atau phpseclib 4).
