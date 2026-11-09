<script type="text/javascript">
$(document).ready(function() {
    // 1. Locate or create parent "Developer" menu in CWP sidebar
    var $developerLi = $("#mn-developer");
    var $developerSub = $("#mn-developer-sub");

    // Check if an existing Developer menu item already exists in the sidebar
    if (!$developerLi.length) {
        // Search by text if CWP rendered it with another ID
        $(".mainnav > ul > li").each(function() {
            var txt = $(this).children("a").text().trim();
            if (txt.indexOf("Developer") !== -1 || txt.indexOf("Geliştirici") !== -1) {
                $developerLi = $(this);
                $developerSub = $(this).find("ul.sub");
                return false;
            }
        });
    }

    // If no Developer parent menu exists, create it cleanly adhering to CWP navigation standards
    if (!$developerLi || !$developerLi.length) {
        var devMenuHtml = '<li id="mn-developer" class="hasSub">'
            + '<a href="#" class="hasUl notExpand">'
            + '<span class="icon16 icomoon-icon-embed2" style="color: #6366f1;"></span>'
            + '<span class="txt" style="font-weight: 600;">Developer</span>'
            + '<span class="hasDrop icon16 icomoon-icon-arrow-down-2"></span>'
            + '</a>'
            + '<ul id="mn-developer-sub" class="sub" style="display: none;"></ul>'
            + '</li>';

        // Insert before 3rdparty/ConfigServer or append to navigation list
        if ($("#mn-12").length) {
            $("#mn-12").after(devMenuHtml);
        } else if ($(".mainnav > ul").length) {
            $(".mainnav > ul").append(devMenuHtml);
        } else if ($("#myList").length) {
            $("#myList").append(devMenuHtml);
        }

        $developerLi = $("#mn-developer");
        $developerSub = $("#mn-developer-sub");
    }

    // 2. Prepend MaxMind GeoIP item as the FIRST element inside the Developer submenu
    if (!$("#mn-geoip-sub").length) {
        var geoipItem = '<li id="mn-geoip-sub">'
            + '<a href="index.php?module=geoip">'
            + '<span class="icon16 icomoon-icon-earth" style="color: #2f80ed !important; margin-right: 6px;"></span>'
            + '<span class="txt">MaxMind GeoIP</span>'
            + '</a>'
            + '</li>';

        $developerSub.prepend(geoipItem);
    }

    // 3. Highlight and keep Developer menu expanded when on GeoIP module
    if (window.location.search.indexOf('module=geoip') !== -1 || window.location.href.indexOf('module=geoip') !== -1) {
        if ($developerSub.length) {
            $developerSub.addClass("show").css("display", "block");
        }
        if ($developerLi.length) {
            $developerLi.addClass("hasSub highlight-menu active-state");
            var $toggle = $developerLi.children("a.hasUl");
            if ($toggle.length) {
                $toggle.removeClass("notExpand").addClass("expand active-state");
            }
        }
        $("#mn-geoip-sub > a").addClass("active");
    }
});
</script>
