<!-- Piwik Tracking Code -->
<script type="text/javascript">
    var pkBaseURL = "{$url}";
    document.write(unescape("%3Cscript src='" + pkBaseURL
            + "piwik.js' type='text/javascript'%3E%3C/script%3E"));
{literal}
    try {
{/literal}
        var piwikTracker = Piwik.getTracker(pkBaseURL + "piwik.php", {$site});
        piwikTracker.trackPageView();
        piwikTracker.enableLinkTracking();
{literal}
    } catch (err) {
    }
{/literal}
</script>
<noscript><p><img src="{$url}piwik.php?idsite={$site}" style="border:0" alt="" /></p></noscript>
<!-- End Piwik Tracking Code -->