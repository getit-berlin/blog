<?php
declare(strict_types=1);

/**
 * Getit-Berlin — single-file blog engine.
 *
 * Renders the migrated wp-migration/ markdown tree as a blog.
 * No dependencies, no database, no build step. Target: PHP 8.4.
 *
 * Layout expected next to this file (or one level up):
 *
 *   index.php
 *   content/
 *     2014/welcome.md
 *     2016/logo-hp.png
 *     ...
 *
 * Routes:
 *   /                     index, newest first
 *   /?p=<slug>            one article
 *   /?tag=<tag>           articles with that tag
 *   /?cat=<category>      articles in that category
 *   /?jahr=<year>         articles from that year
 *   /?img=<year>/<file>   image asset (streamed, so content/ may sit outside the docroot)
 */

// --------------------------------------------------------------------- config

const SITE_TITLE = 'Getit-Berlin';
const SITE_LANG  = 'de';
const AUTHOR_URL = 'https://github.com/getit-berlin';
const PER_PAGE   = 0;            // 0 = no paging, list everything
const CACHE_SECS = 3600;         // cache header for streamed images

/** Candidate content directories, first hit wins. */
const CONTENT_DIRS = [
    __DIR__ . '/content',
    __DIR__ . '/../content',
];

if (PHP_VERSION_ID < 80100) {
    http_response_code(500);
    exit('This blog needs PHP 8.1 or newer (built for 8.4); running ' . PHP_VERSION);
}

// ---------------------------------------------------------------------- model

final class Article
{
    /**
     * @param list<string> $categories
     * @param list<string> $tags
     * @param list<string> $bodyLines
     */
    public function __construct(
        public readonly string $slug,
        public readonly string $title,
        public readonly string $author,
        public readonly string $date,
        public readonly string $year,
        public readonly array  $categories,
        public readonly array  $tags,
        public readonly array  $bodyLines,
    ) {
    }

    public function url(): string
    {
        return '?p=' . rawurlencode($this->slug);
    }

    public function dateLong(): string
    {
        $ts = strtotime($this->date);
        if ($ts === false) {
            return $this->date;
        }

        $months = [
            1 => 'Januar', 'Februar', 'März', 'April', 'Mai', 'Juni',
            'Juli', 'August', 'September', 'Oktober', 'November', 'Dezember',
        ];

        return (int) date('j', $ts) . '. ' . $months[(int) date('n', $ts)] . ' ' . date('Y', $ts);
    }

    /** Plain-text opening, for the index listing. */
    public function excerpt(int $chars = 180): string
    {
        $text = '';

        foreach ($this->bodyLines as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '```') || str_starts_with($line, '![')
                || str_starts_with($line, '|') || str_starts_with($line, '>')) {
                continue;
            }
            $text = $line;
            break;
        }

        // strip the markdown we care about, leave readable prose
        $text = preg_replace('/!?\[([^\]]*)\]\([^)]*\)/u', '$1', $text) ?? $text;
        $text = str_replace(['**', '`', '_'], '', $text);
        $text = trim($text);

        // UTF-8 aware without mbstring, which shared hosts do not always enable
        $glyphs = preg_split('//u', $text, -1, PREG_SPLIT_NO_EMPTY) ?: [];

        if (count($glyphs) <= $chars) {
            return $text;
        }

        $cut = implode('', array_slice($glyphs, 0, $chars));
        $sp  = strrpos($cut, ' ');

        return rtrim($sp !== false ? substr($cut, 0, $sp) : $cut, " ,.;:") . ' …';
    }
}

// ------------------------------------------------------------------ repository

function contentDir(): ?string
{
    static $dir = false;

    if ($dir !== false) {
        return $dir;
    }

    $dir = null;
    foreach (CONTENT_DIRS as $candidate) {
        if (is_dir($candidate)) {
            $dir = realpath($candidate) ?: $candidate;
            break;
        }
    }

    return $dir;
}

/** @return list<Article> newest first */
function articles(): array
{
    static $cache = null;

    if ($cache !== null) {
        return $cache;
    }

    $cache = [];
    $dir   = contentDir();

    if ($dir === null) {
        return $cache;
    }

    $walk = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS)
    );

    foreach ($walk as $file) {
        if (!$file->isFile() || strtolower($file->getExtension()) !== 'md') {
            continue;
        }
        $article = parseArticle($file->getPathname());
        if ($article !== null) {
            $cache[] = $article;
        }
    }

    usort($cache, static fn (Article $a, Article $b): int => $b->date <=> $a->date);

    return $cache;
}

function parseArticle(string $path): ?Article
{
    $raw = file_get_contents($path);
    if ($raw === false) {
        return null;
    }

    $lines = explode("\n", str_replace(["\r\n", "\r"], "\n", $raw));

    // "## Title" must be first
    $first = trim(array_shift($lines) ?? '');
    if (!str_starts_with($first, '## ')) {
        return null;
    }
    $title = trim(substr($first, 3));

    $author = '';
    $date   = '';
    $cats   = [];

    // header block: von / am / in, in that order, blank lines between
    while ($lines !== []) {
        $probe = trim($lines[0]);

        if ($probe === '') {
            array_shift($lines);
            continue;
        }

        if ($author === '' && str_starts_with($probe, 'von ')) {
            $author = trim(substr($probe, 4));
            array_shift($lines);
            continue;
        }
        if ($date === '' && str_starts_with($probe, 'am ')) {
            $date = trim(substr($probe, 3));
            array_shift($lines);
            continue;
        }
        if ($cats === [] && str_starts_with($probe, 'in ')) {
            $cats = array_values(array_filter(
                array_map(trim(...), explode(',', substr($probe, 3))),
                static fn (string $c): bool => $c !== ''
            ));
            array_shift($lines);
            continue;
        }

        break; // body starts here
    }

    // trailing "tag a b c" line, if present
    $tags = [];
    for ($i = count($lines) - 1; $i >= 0; $i--) {
        $probe = trim($lines[$i]);
        if ($probe === '') {
            continue;
        }
        if (str_starts_with($probe, 'tag ')) {
            $tags = array_values(array_filter(preg_split('/\s+/', substr($probe, 4)) ?: []));
            array_splice($lines, $i);
        }
        break;
    }

    $slug = basename($path, '.md');
    $year = basename(dirname($path));

    if (!preg_match('/^\d{4}$/', $year)) {
        $year = substr($date, 0, 4);
    }

    return new Article($slug, $title, $author, $date, $year, $cats, $tags, $lines);
}

function articleBySlug(string $slug): ?Article
{
    foreach (articles() as $article) {
        if ($article->slug === $slug) {
            return $article;
        }
    }

    return null;
}

/**
 * Count distinct values of a field. Works for list fields (tags, categories)
 * and scalar ones (year).
 *
 * @return array<string,int> value => count
 */
function facet(string $field, bool $sortByKeyDesc = false): array
{
    $out = [];

    foreach (articles() as $article) {
        $value = $article->{$field};

        foreach (is_array($value) ? $value : [$value] as $item) {
            $item = (string) $item;
            if ($item === '') {
                continue;
            }
            $out[$item] = ($out[$item] ?? 0) + 1;
        }
    }

    $sortByKeyDesc ? krsort($out) : arsort($out);

    return $out;
}

// ------------------------------------------------------------------- markdown

/**
 * Render the markdown subset this corpus uses:
 * fenced code, blockquotes (with "— attribution"), unordered lists, tables,
 * images, links, bold, italic, inline code, bare URLs.
 *
 * @param list<string> $lines
 */
function renderMarkdown(array $lines, string $year): string
{
    $html = [];
    $n    = count($lines);

    for ($i = 0; $i < $n; $i++) {
        $line = rtrim($lines[$i]);
        $trim = trim($line);

        if ($trim === '') {
            continue;
        }

        // fenced code
        if (str_starts_with($trim, '```')) {
            $buf = [];
            $i++;
            while ($i < $n && !str_starts_with(trim($lines[$i]), '```')) {
                $buf[] = rtrim($lines[$i]);
                $i++;
            }
            $html[] = '<pre><code>'
                . htmlspecialchars(implode("\n", $buf), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')
                . '</code></pre>';
            continue;
        }

        // blockquote
        if (str_starts_with($trim, '>')) {
            $buf = [];
            while ($i < $n && str_starts_with(trim($lines[$i]), '>')) {
                $buf[] = ltrim(substr(trim($lines[$i]), 1));
                $i++;
            }
            $i--;

            $cite = null;
            for ($k = count($buf) - 1; $k >= 0; $k--) {
                if (trim($buf[$k]) === '') {
                    continue;
                }
                if (str_starts_with(trim($buf[$k]), '—')) {
                    $cite = trim(ltrim(trim($buf[$k]), '—'));
                    array_splice($buf, $k);
                }
                break;
            }

            $body = implode(' ', array_filter(array_map(trim(...), $buf), static fn ($s) => $s !== ''));
            $out  = '<blockquote><p>' . inlineMarkdown($body, $year) . '</p>';
            if ($cite !== null && $cite !== '') {
                $out .= '<cite>' . inlineMarkdown($cite, $year) . '</cite>';
            }
            $html[] = $out . '</blockquote>';
            continue;
        }

        // unordered list
        if (str_starts_with($trim, '- ')) {
            $items = [];
            while ($i < $n && str_starts_with(trim($lines[$i]), '- ')) {
                $items[] = '<li>' . inlineMarkdown(substr(trim($lines[$i]), 2), $year) . '</li>';
                $i++;
            }
            $i--;
            $html[] = '<ul>' . implode('', $items) . '</ul>';
            continue;
        }

        // table
        if (str_starts_with($trim, '|')) {
            $rows = [];
            while ($i < $n && str_starts_with(trim($lines[$i]), '|')) {
                $rows[] = trim($lines[$i]);
                $i++;
            }
            $i--;
            $html[] = renderTable($rows, $year);
            continue;
        }

        // standalone image
        if (preg_match('/^!\[([^\]]*)\]\(([^)]+)\)$/u', $trim, $m) === 1) {
            $html[] = '<figure>' . imageTag($m[2], $m[1], $year)
                . ($m[1] !== '' ? '<figcaption>'
                    . htmlspecialchars($m[1], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')
                    . '</figcaption>' : '')
                . '</figure>';
            continue;
        }

        // paragraph
        $buf = [];
        while ($i < $n) {
            $cur = trim($lines[$i]);
            if ($cur === '' || str_starts_with($cur, '```') || str_starts_with($cur, '>')
                || str_starts_with($cur, '- ') || str_starts_with($cur, '|')
                || preg_match('/^!\[([^\]]*)\]\(([^)]+)\)$/u', $cur) === 1) {
                break;
            }
            $buf[] = $cur;
            $i++;
        }
        $i--;
        $html[] = '<p>' . inlineMarkdown(implode(' ', $buf), $year) . '</p>';
    }

    return implode("\n", $html);
}

/** @param list<string> $rows */
function renderTable(array $rows, string $year): string
{
    $cells = static function (string $row): array {
        $row = trim($row);
        $row = preg_replace('/^\|/', '', $row) ?? $row;
        $row = preg_replace('/\|$/', '', $row) ?? $row;

        return array_map(trim(...), explode('|', $row));
    };

    $head = $cells($rows[0]);
    $body = [];
    $start = 1;

    // skip the |---|---| separator row if present
    if (isset($rows[1]) && preg_match('/^\|[\s:|-]+\|?$/', trim($rows[1])) === 1) {
        $start = 2;
    }

    for ($i = $start; $i < count($rows); $i++) {
        $body[] = $cells($rows[$i]);
    }

    $out = '<div class="scroll"><table><thead><tr>';
    foreach ($head as $cell) {
        $out .= '<th>' . inlineMarkdown($cell, $year) . '</th>';
    }
    $out .= '</tr></thead><tbody>';

    foreach ($body as $row) {
        $out .= '<tr>';
        foreach ($row as $cell) {
            $out .= '<td>' . inlineMarkdown($cell, $year) . '</td>';
        }
        $out .= '</tr>';
    }

    return $out . '</tbody></table></div>';
}

function imageTag(string $src, string $alt, string $year): string
{
    $url = str_contains($src, '://')
        ? $src
        : '?img=' . rawurlencode($year) . '/' . rawurlencode($src);

    return '<img src="' . htmlspecialchars($url, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')
        . '" alt="' . htmlspecialchars($alt, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')
        . '" loading="lazy">';
}

/**
 * Inline formatting. The string is HTML-escaped first, then markdown is applied
 * to the escaped text, so no user content can inject markup. A literal <br> is
 * the one tag allowed back through, because the migrated tables rely on it.
 */
function inlineMarkdown(string $text, string $year): string
{
    $store = [];
    $keep  = static function (string $html) use (&$store): string {
        $store[] = $html;

        return "\x02" . (count($store) - 1) . "\x03";
    };

    $out = htmlspecialchars($text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    $out = str_replace(['&lt;br&gt;', '&lt;br/&gt;', '&lt;br /&gt;'], '<br>', $out);

    // inline code first, so its contents are never reformatted
    $out = preg_replace_callback(
        '/`([^`]+)`/u',
        static fn (array $m): string => $keep('<code>' . $m[1] . '</code>'),
        $out
    ) ?? $out;

    // escaped brackets, e.g. [\[OmniOS-discuss\] ...](url)
    $out = str_replace(['\\[', '\\]'], ["\x04", "\x05"], $out);

    // images
    $out = preg_replace_callback(
        '/!\[([^\]]*)\]\(([^)\s]+)\)/u',
        static fn (array $m): string => $keep(imageTag($m[2], $m[1], $year)),
        $out
    ) ?? $out;

    // links
    $out = preg_replace_callback(
        '/\[([^\]]*)\]\(([^)\s]+)\)/u',
        static function (array $m) use ($keep): string {
            $label = str_replace(["\x04", "\x05"], ['[', ']'], $m[1]);
            $label = preg_replace('/\*\*(.+?)\*\*/u', '<strong>$1</strong>', $label) ?? $label;
            $href  = html_entity_decode($m[2], ENT_QUOTES, 'UTF-8');
            $href  = htmlspecialchars($href, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

            return $keep('<a href="' . $href . '" rel="noopener">' . $label . '</a>');
        },
        $out
    ) ?? $out;

    // emphasis
    $out = preg_replace('/\*\*(.+?)\*\*/u', '<strong>$1</strong>', $out) ?? $out;
    $out = preg_replace('/(?<![\w\\\\])_(?=\S)(.+?)(?<=\S)_(?!\w)/u', '<em>$1</em>', $out) ?? $out;
    $out = preg_replace('/(?<![\w*])\*(?=\S)([^*]+?)(?<=\S)\*(?!\w)/u', '<em>$1</em>', $out) ?? $out;

    // bare URLs
    $out = preg_replace_callback(
        '~(?<![">=])\bhttps?://[^\s<>()]+[^\s<>().,;:!?]~u',
        static fn (array $m): string => $keep(
            '<a href="' . $m[0] . '" rel="noopener">' . $m[0] . '</a>'
        ),
        $out
    ) ?? $out;

    $out = str_replace(["\x04", "\x05"], ['[', ']'], $out);

    // restore protected fragments
    return preg_replace_callback(
        '/\x02(\d+)\x03/',
        static fn (array $m): string => $store[(int) $m[1]] ?? '',
        $out
    ) ?? $out;
}

// ---------------------------------------------------------------------- image

function serveImage(string $rel): never
{
    $dir = contentDir();

    // reject traversal; only <year>/<file.ext> is allowed
    if ($dir === null || preg_match('~^\d{4}/[A-Za-z0-9._-]+$~', $rel) !== 1) {
        http_response_code(404);
        exit;
    }

    $path = $dir . '/' . $rel;
    $real = realpath($path);

    if ($real === false || !str_starts_with($real, $dir) || !is_file($real)) {
        http_response_code(404);
        exit;
    }

    $type = match (strtolower(pathinfo($real, PATHINFO_EXTENSION))) {
        'png'          => 'image/png',
        'jpg', 'jpeg'  => 'image/jpeg',
        'gif'          => 'image/gif',
        'webp'         => 'image/webp',
        'svg'          => 'image/svg+xml',
        default        => null,
    };

    if ($type === null) {
        http_response_code(404);
        exit;
    }

    header('Content-Type: ' . $type);
    header('Content-Length: ' . filesize($real));
    header('Cache-Control: public, max-age=' . CACHE_SECS);
    readfile($real);
    exit;
}

// ------------------------------------------------------------------ templates

function e(string $s): string
{
    return htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function layout(string $title, string $body, string $subtitle = ''): string
{
    $head = $title === '' ? SITE_TITLE : $title . ' · ' . SITE_TITLE;
    $tags = facet('tags');
    $top  = array_slice($tags, 0, 18, true);

    $cloud = '';
    foreach ($top as $tag => $count) {
        $cloud .= '<a href="?tag=' . rawurlencode((string) $tag) . '">' . e((string) $tag)
            . '<span>' . $count . '</span></a> ';
    }

    $years = '';
    foreach (facet('year', sortByKeyDesc: true) as $year => $count) {
        $years .= '<a href="?jahr=' . rawurlencode((string) $year) . '">' . e((string) $year)
            . '<span>' . $count . '</span></a> ';
    }

    $count = count(articles());
    $sub   = $subtitle !== '' ? '<p class="tag">' . e($subtitle) . '</p>' : '';

    return <<<HTML
    <!DOCTYPE html>
    <html lang="{$GLOBALS['__lang']}">
    <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{$head}</title>
    <style>{$GLOBALS['__css']}</style>
    </head>
    <body>
    <header class="site">
      <a class="brand" href="?">{$GLOBALS['__site']}</a>
      <nav><a href="?">Archiv</a></nav>
    </header>
    <main>
    {$sub}
    {$body}
    </main>
    <aside>
      <h2>Jahre</h2>
      <div class="chips">{$years}</div>
      <h2>Tags</h2>
      <div class="chips">{$cloud}</div>
    </aside>
    <footer>
      {$count} Artikel · 2014–2023 · <a href="{$GLOBALS['__author']}" rel="noopener">Getit-Berlin</a>
    </footer>
    </body>
    </html>
    HTML;
}

/** @param list<Article> $list */
function renderList(array $list): string
{
    if ($list === []) {
        return '<p class="empty">Keine Artikel gefunden.</p>';
    }

    $out      = '';
    $lastYear = null;

    foreach ($list as $article) {
        if ($article->year !== $lastYear) {
            if ($lastYear !== null) {
                $out .= '</div>';
            }
            $out .= '<h2 class="year">' . e($article->year) . '</h2><div class="entries">';
            $lastYear = $article->year;
        }

        $cats = '';
        foreach ($article->categories as $cat) {
            $cats .= '<a class="cat" href="?cat=' . rawurlencode($cat) . '">' . e($cat) . '</a>';
        }

        $out .= '<article class="entry">'
            . '<a class="t" href="' . e($article->url()) . '">' . e($article->title) . '</a>'
            . '<p class="x">' . e($article->excerpt()) . '</p>'
            . '<p class="m"><time>' . e($article->dateLong()) . '</time>' . $cats . '</p>'
            . '</article>';
    }

    return $out . '</div>';
}

function renderArticle(Article $article): string
{
    $cats = '';
    foreach ($article->categories as $cat) {
        $cats .= '<a class="cat" href="?cat=' . rawurlencode($cat) . '">' . e($cat) . '</a>';
    }

    $tags = '';
    foreach ($article->tags as $tag) {
        $tags .= '<a href="?tag=' . rawurlencode($tag) . '">' . e($tag) . '</a> ';
    }

    $body = renderMarkdown($article->bodyLines, $article->year);

    return '<article class="post">'
        . '<h1>' . e($article->title) . '</h1>'
        . '<p class="m">von ' . e($article->author) . ' · <time>' . e($article->dateLong()) . '</time>'
        . $cats . '</p>'
        . '<div class="body">' . $body . '</div>'
        . ($tags !== '' ? '<p class="tags">' . $tags . '</p>' : '')
        . '<p class="back"><a href="?">← Archiv</a></p>'
        . '</article>';
}

// --------------------------------------------------------------------- styles

$GLOBALS['__css'] = <<<'CSS'
:root{color-scheme:light dark;--bg:#fbfbfa;--fg:#1c1c1a;--muted:#6b6b66;--rule:#e4e4df;--accent:#1f6f5c;--code:#f2f2ee}
@media (prefers-color-scheme:dark){:root{--bg:#16171a;--fg:#e8e8e4;--muted:#9a9a94;--rule:#2c2e33;--accent:#5fbfa4;--code:#1e2024}}
*{box-sizing:border-box}
body{margin:0;background:var(--bg);color:var(--fg);font:16px/1.65 ui-sans-serif,system-ui,-apple-system,"Segoe UI",Roboto,Helvetica,Arial,sans-serif;-webkit-font-smoothing:antialiased}
a{color:var(--accent)}
header.site{max-width:48rem;margin:0 auto;padding:2rem 1.5rem 0;display:flex;justify-content:space-between;align-items:baseline;gap:1rem}
.brand{font-weight:700;font-size:1.1rem;letter-spacing:-.01em;text-decoration:none;color:var(--fg)}
header.site nav a{font-size:.9rem;color:var(--muted);text-decoration:none}
main,aside,footer{max-width:48rem;margin:0 auto;padding:0 1.5rem}
main{padding-top:2rem}
.tag{color:var(--accent);font-size:.78rem;font-weight:600;letter-spacing:.08em;text-transform:uppercase;margin:0 0 1.5rem}
h2.year{font-size:.8rem;letter-spacing:.1em;color:var(--muted);margin:2.5rem 0 .75rem;padding-bottom:.4rem;border-bottom:1px solid var(--rule);font-variant-numeric:tabular-nums}
.entries{display:flex;flex-direction:column;gap:1.4rem}
.entry .t{display:block;font-weight:600;font-size:1.05rem;text-decoration:none;color:var(--fg);letter-spacing:-.01em}
.entry .t:hover{color:var(--accent)}
.entry .x{margin:.2rem 0 .3rem;color:var(--muted);font-size:.92rem}
.entry .m,.post .m{margin:0;font-size:.8rem;color:var(--muted);display:flex;flex-wrap:wrap;gap:.5rem;align-items:center}
.cat{font-size:.72rem;text-decoration:none;border:1px solid var(--rule);border-radius:999px;padding:.05rem .5rem;color:var(--muted)}
.post h1{font-size:1.7rem;letter-spacing:-.02em;margin:0 0 .5rem;line-height:1.25}
.post .m{margin-bottom:2rem;padding-bottom:1.25rem;border-bottom:1px solid var(--rule)}
.body p{margin:0 0 1.1rem}
.body ul{margin:0 0 1.1rem;padding-left:1.2rem}
.body li{margin:.2rem 0}
.body h2{font-size:1.2rem;margin:2rem 0 .6rem}
.body pre{background:var(--code);border:1px solid var(--rule);border-radius:6px;padding:.9rem 1rem;overflow-x:auto;margin:0 0 1.2rem}
.body pre code{font:.82rem/1.5 ui-monospace,SFMono-Regular,Menlo,Consolas,monospace;background:none;padding:0}
.body code{font-family:ui-monospace,SFMono-Regular,Menlo,Consolas,monospace;font-size:.875em;background:var(--code);padding:.1em .35em;border-radius:3px}
.body blockquote{margin:0 0 1.2rem;padding:.2rem 0 .2rem 1.1rem;border-left:3px solid var(--accent)}
.body blockquote p{margin:0 0 .4rem}
.body cite{font-style:normal;font-size:.85rem;color:var(--muted)}
.body figure{margin:0 0 1.4rem}
.body img{max-width:100%;height:auto;border-radius:5px;border:1px solid var(--rule);display:block}
.body figcaption{font-size:.8rem;color:var(--muted);margin-top:.4rem}
.scroll{overflow-x:auto;margin:0 0 1.2rem}
.body table{border-collapse:collapse;font-size:.85rem;min-width:100%}
.body th,.body td{border:1px solid var(--rule);padding:.4rem .6rem;text-align:left;vertical-align:top}
.body th{background:var(--code);font-weight:600}
.tags{margin:2rem 0 0;padding-top:1.25rem;border-top:1px solid var(--rule);font-size:.78rem}
.tags a{display:inline-block;text-decoration:none;color:var(--muted);border:1px solid var(--rule);border-radius:999px;padding:.05rem .55rem;margin:0 .2rem .35rem 0}
.back{margin:2rem 0 0;font-size:.9rem}
aside{margin-top:4rem;padding-top:2rem;border-top:1px solid var(--rule)}
aside h2{font-size:.75rem;letter-spacing:.1em;text-transform:uppercase;color:var(--muted);margin:1.5rem 0 .6rem}
.chips a{display:inline-block;font-size:.78rem;text-decoration:none;color:var(--muted);border:1px solid var(--rule);border-radius:999px;padding:.1rem .55rem;margin:0 .25rem .4rem 0}
.chips a:hover{color:var(--accent);border-color:var(--accent)}
.chips span{opacity:.55;margin-left:.35rem;font-variant-numeric:tabular-nums}
.empty{color:var(--muted)}
footer{margin:3rem auto;padding-top:1.5rem;border-top:1px solid var(--rule);font-size:.82rem;color:var(--muted)}
CSS;

$GLOBALS['__lang']   = SITE_LANG;
$GLOBALS['__site']   = e(SITE_TITLE);
$GLOBALS['__author'] = e(AUTHOR_URL);

// --------------------------------------------------------------------- router

$img = $_GET['img'] ?? null;
if (is_string($img) && $img !== '') {
    serveImage($img);
}

if (contentDir() === null) {
    http_response_code(503);
    header('Content-Type: text/html; charset=utf-8');
    echo layout('Kein Inhalt', '<p class="empty">Content directory not found. Expected one of:<br><code>'
        . implode('</code><br><code>', array_map(e(...), CONTENT_DIRS)) . '</code></p>');
    exit;
}

header('Content-Type: text/html; charset=utf-8');

$slug = $_GET['p']    ?? null;
$tag  = $_GET['tag']  ?? null;
$cat  = $_GET['cat']  ?? null;
$year = $_GET['jahr'] ?? null;

if (is_string($slug) && $slug !== '') {
    $article = articleBySlug($slug);

    if ($article === null) {
        http_response_code(404);
        echo layout('Nicht gefunden', '<p class="empty">Diesen Artikel gibt es nicht. <a href="?">Zum Archiv</a></p>');
        exit;
    }

    echo layout($article->title, renderArticle($article));
    exit;
}

if (is_string($tag) && $tag !== '') {
    $list = array_values(array_filter(
        articles(),
        static fn (Article $a): bool => in_array($tag, $a->tags, true)
    ));
    echo layout('Tag: ' . $tag, renderList($list), 'Tag · ' . $tag);
    exit;
}

if (is_string($cat) && $cat !== '') {
    $list = array_values(array_filter(
        articles(),
        static fn (Article $a): bool => in_array($cat, $a->categories, true)
    ));
    echo layout('Kategorie: ' . $cat, renderList($list), 'Kategorie · ' . $cat);
    exit;
}

if (is_string($year) && $year !== '') {
    $list = array_values(array_filter(
        articles(),
        static fn (Article $a): bool => $a->year === $year
    ));
    echo layout('Jahr: ' . $year, renderList($list), 'Jahr · ' . $year);
    exit;
}

echo layout('', renderList(articles()));
