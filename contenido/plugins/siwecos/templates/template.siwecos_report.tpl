<div class="mod_siwecos">
    <div id="mod_siwecos_results" style="" class="container-fluid">
        <div class="row-fluid">
            <div class="span4">
                <div data-size="200" data-width="20" data-style="Arch" data-theme="Red-Gold-Green"
                     data-animate_gauge_colors="1" class="GaugeMeter big" style="width: 200px;">
                </div>
            </div>
            <div class="span8">
                <h1 class="text-center">{i18n("TTL_SCAN_RESULTS", 'siwecos')}</h1>
                <div class="last-scan-data">
                    <span>{$resultjson->scanFinished->date|date_format:"%d %m %Y %H:%M:%S"}</span>
                </div>
                <br>
                <div class="scanners-wrapper" style="">
                    {foreach item=item from=$resultjson->scanners}
                        <div class="scanner-content">
                            <div class="scanner-item-data">
                                <span class="scanner-data">{$item->scanner_type}</span>
                                <a href="https://www.siwecos.de/wiki/{$item->scanner_type}&#10;/{$language}"
                                   title="{i18n("BTN_BACKGROUND_INFO", 'siwecos')}"
                                   target="_blank">{i18n("BTN_BACKGROUND_INFO", 'siwecos')}
                                    &gt;&gt;</a>
                                <div class="GaugeMeter scanner-gauge{$item@key}"></div>
                                <script type="text/javascript">
                                    $(document).ready(function () {
                                        $(".scanners-wrapper .scanner-gauge{$item@key}").data('percent', parseInt({$item->total_score}));
                                        $(".scanners-wrapper .scanner-gauge{$item@key}").gaugeMeter();
                                    });
                                </script>
                                <div class="scanner-check-wrapper">
                                    {foreach item=val from=$item->result}
                                        <div class="scanner-check-content">
                                            <div class="scanner-check-item-data">
                                                <div class="col-85">
                                                    <span class="round-check scanner-check-data score-{if {$val->score} lte 50}red{/if}{if {$val->score} gt 50 and {$val->score} lt 100}yellow{/if}{if {$val->score} eq 100}green{/if}">{$val->name}</span>
                                                </div>
                                                <div class="col-20">
                                                    <button class="btn btn-primary"
                                                            onclick="showDescription(this)">
                                                        <span>{i18n("BTN_EXPAND", 'siwecos')}</span>
                                                    </button>
                                                </div>
                                                <div style="clear: both;"></div>
                                                <div class="scanner-check-item-description"
                                                     style="display: none;">
                                                    <p class="scanner-check-item-description-title">{$val->description}</p>
                                                    <p class="scanner-check-item-description-report">{$val->report}</p>
                                                    <div style="clear: both;"></div>
                                                    <small><a href="{$val->link}"
                                                              target="_blank">{i18n("BTN_BACKGROUND_INFO", 'siwecos')}</a>
                                                    </small>
                                                    <div style="clear: both;"></div>
                                                    <div style="clear: both; padding-bottom: 20px;"></div>
                                                </div>
                                            </div>
                                            <div style="clear: both;"></div>
                                        </div>
                                    {/foreach}
                                </div>
                            </div>
                        </div>
                    {/foreach}
                    <div class="seal-link">
                        <a href="https://www.siwecos.de/wiki/Siwecos-Siegel/{$LANG}?userdomain={$resultjson->domain}"
                           target="_blank">{$howBtn}</a>
                    </div>
                </div>
                <button id="siwecosStartScanBtn" class="btn-primary btn"><a
                            href="{$scanHref}">{i18n("BTN_START_SCAN", 'siwecos')}</a></button>
                <a href="https://siwecos.de/app/#/domains" target="_blank"
                   class="btn-secondary btn">{i18n("BTN_SIWECOS_SITE", 'siwecos')}</a>
            </div>
        </div>
    </div>
</div>
<br>
{*<pre>*}
{*{$results|@print_r}*}
{*</pre>*}
<script type="text/javascript">
    $(document).ready(function () {
        var responseData = {$result};
        var elem = $('#mod_siwecos_results').find('.big');
        elem.data('percent', parseInt(responseData.weightedMedia));
        elem.gaugeMeter();
    });

    function showDescription(ele) {
        var divEle = ele.closest('.scanner-check-content');
        if (divEle.getElementsByClassName('scanner-check-item-description')[0].style.display === "block") {
            divEle.getElementsByClassName('scanner-check-item-description')[0].style.display = "none";
            ele.textContent = '{i18n("BTN_EXPAND", 'siwecos')}';
        } else {
            divEle.getElementsByClassName('scanner-check-item-description')[0].style.display = "block";
            ele.textContent = '{i18n("BTN_COLLAPSE", 'siwecos')}';
        }
    }
</script>