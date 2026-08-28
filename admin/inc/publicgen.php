<?php
/**
 * Renders integration settings (Search Console, analytics, custom snippets)
 * into two cached partials that the public pages @include. Regenerated on save,
 * so the public site never has to touch the database.
 */

function public_inc_dir(): string { return dirname(__DIR__, 2) . '/assets/inc'; }

function regenerate_public_snippets(): bool {
    $dir = public_inc_dir();
    if (!is_dir($dir)) @mkdir($dir, 0755, true);

    $gsc  = trim(setting('gsc_verification'));
    $bing = trim(setting('bing_verification'));
    $ga4  = trim(setting('ga4_id'));
    $gtm  = trim(setting('gtm_id'));
    $head = setting('head_snippet');
    $body = setting('body_snippet');

    // ---- <head> partial --------------------------------------------------
    $h  = "<!-- Auto-generated from Admin → Settings. Do not edit by hand. -->\n";
    if ($gsc !== '')  $h .= '<meta name="google-site-verification" content="' . e($gsc) . "\">\n";
    if ($bing !== '') $h .= '<meta name="msvalidate.01" content="' . e($bing) . "\">\n";
    if (preg_match('/^G-[A-Z0-9]+$/i', $ga4)) {
        $h .= '<script async src="https://www.googletagmanager.com/gtag/js?id=' . e($ga4) . "\"></script>\n";
        $h .= "<script>window.dataLayer=window.dataLayer||[];function gtag(){dataLayer.push(arguments);}gtag('js',new Date());gtag('config','" . e($ga4) . "');</script>\n";
    }
    if (preg_match('/^GTM-[A-Z0-9]+$/i', $gtm)) {
        $h .= "<script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src='https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);})(window,document,'script','dataLayer','" . e($gtm) . "');</script>\n";
    }
    if (trim($head) !== '') $h .= $head . "\n";

    // ---- pre-</body> partial --------------------------------------------
    $b = "<!-- Auto-generated from Admin → Settings. -->\n";
    if (preg_match('/^GTM-[A-Z0-9]+$/i', $gtm)) {
        $b .= '<noscript><iframe src="https://www.googletagmanager.com/ns.html?id=' . e($gtm)
            . '" height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>' . "\n";
    }
    if (trim($body) !== '') $b .= $body . "\n";

    $ok1 = @file_put_contents($dir . '/site-head.php', $h) !== false;
    $ok2 = @file_put_contents($dir . '/site-body.php', $b) !== false;
    return $ok1 && $ok2;
}
