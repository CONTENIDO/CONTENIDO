<!-- Piwik Tracking Code -->
<script type="text/javascript">
    var piwikBaseURL = "{$url|escape:'javascript'}";
    var piwikSite = "{$site|escape:'javascript'}";

    {literal}
    (function () {
        var element = document.createElement('script');
        element.type = 'text/javascript';
        element.async = false;
        element.src = piwikBaseURL + 'piwik.js';
        var s = document.getElementsByTagName('script')[0];
        s.parentNode.insertBefore(element, s);

        window.addEventListener('load', function () {
            try {
                var piwikTracker = Piwik.getTracker(piwikBaseURL + 'piwik.php', piwikSite);
                piwikTracker.trackPageView();
                piwikTracker.enableLinkTracking();
            } catch (err) {
            }
        });
    })();
    {/literal}
</script>
<noscript>
    <p><img src="{$url|escape}piwik.php?idsite={$site|escape}" style="border:0" alt=""/></p>
</noscript>
<!-- End Piwik Tracking Code -->