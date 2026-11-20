jQuery(document).ready(function ($) {
  // AI Logic removed per user request (Simplified UI)

  const tabStorageKey = "vonseowp_active_settings_tab";
  const getValidTab = function (tabName) {
    return tabName && $("#" + tabName + ".von-tab-content").length
      ? tabName
      : "tab-general";
  };

  const activateAdminTab = function (tabName, opts) {
    const options = opts || {};
    const activeTab = getValidTab(tabName);

    $(".von-tab-content").hide();
    $(".von-tab-link").removeClass("active");
    $("#" + activeTab).show();
    $(".von-form-actions").toggle(activeTab !== "tab-transfer");
    $('.von-tab-link[href="#' + activeTab + '"]').addClass("active");

    try {
      window.sessionStorage.setItem(tabStorageKey, activeTab);
    } catch (error) {
      // Some hardened browsers disable sessionStorage; tab switching still works.
    }

    if (
      options.updateHash !== false &&
      window.history &&
      window.history.replaceState
    ) {
      window.history.replaceState(null, "", "#" + activeTab);
    }

    if (options.scroll !== false) {
      window.scrollTo({ top: 0, behavior: "smooth" });
    }
  };

  // Tab switching (used by inline onclick handlers in the settings partial)
  window.openTab = function (evt, tabName) {
    if (evt) evt.preventDefault();
    activateAdminTab(tabName);
  };

  $(".von-settings-form").on("submit", function () {
    const activeTab = $(".von-tab-link.active").attr("href");
    if (activeTab) {
      try {
        window.sessionStorage.setItem(
          tabStorageKey,
          activeTab.replace("#", ""),
        );
      } catch (error) {
        // Saving should never be blocked by tab-state storage.
      }
    }
  });

  const hashTab = window.location.hash
    ? window.location.hash.replace("#", "")
    : "";
  let storedTab = "";
  try {
    storedTab = window.sessionStorage.getItem(tabStorageKey) || "";
  } catch (error) {
    storedTab = "";
  }
  activateAdminTab(hashTab || storedTab || "tab-general", {
    scroll: false,
    updateHash: !!hashTab,
  });

  // --- ROBOTS.TXT RESET ---
  $("#von-reset-robots").on("click", function () {
    if (
      !confirm(
        vonseowp_admin_data.confirm_reset_robots ||
          "Are you sure you want to reset robots.txt to recommended Pro rules?",
      )
    )
      return;

    const proRules =
      typeof vonseowp_admin_data !== "undefined" &&
      vonseowp_admin_data.default_robots_txt
        ? vonseowp_admin_data.default_robots_txt
        : "User-agent: *";

    $("#vonseowp_robots_txt").val(proRules);
  });

  // --- LLM RESET ---
  $("#von-reset-llm").on("click", function () {
    if (
      !confirm(
        vonseowp_admin_data.confirm_reset_llm ||
          "Are you sure you want to reset llms.txt to the recommended AEO template?",
      )
    )
      return;

    const siteName = vonseowp_admin_data.site_name || window.location.hostname;
    const siteDesc = String(vonseowp_admin_data.site_desc || "").trim();
    const homeUrl = String(
      vonseowp_admin_data.home_url || window.location.origin,
    ).replace(/\/+$/, "");

    const llmTemplate = [
      "# " + siteName,
      "",
      ...(siteDesc ? ["> " + siteDesc, ""] : []),
      "## " + vonseowp_admin_data.llm_template.key_resources,
      "",
      "- [" +
        vonseowp_admin_data.llm_template.home_page +
        "](" +
        homeUrl +
        "/): " +
        vonseowp_admin_data.llm_template.entry_point,
      "- [" +
        vonseowp_admin_data.llm_template.xml_sitemap +
        "](" +
        homeUrl +
        "/sitemap.xml): " +
        vonseowp_admin_data.llm_template.full_index,
      "",
      "## " + vonseowp_admin_data.llm_template.about,
      vonseowp_admin_data.llm_template.aeo_footer,
    ].join("\n");

    $("#vonseowp_llms_txt").val(llmTemplate);
  });
});
