jQuery(document).ready(function ($) {
  const truncateAtWordBoundary = (value, maxLength) => {
    const text = String(value || "").replace(/\s+/g, " ").trim();
    if (text.length <= maxLength) return text;

    const slice = text.substring(0, maxLength - 3).trim();
    const lastSpace = slice.lastIndexOf(" ");
    const minimumBoundary = Math.floor(maxLength * 0.6);
    const truncated = lastSpace >= minimumBoundary ? slice.substring(0, lastSpace) : slice;

    return truncated.trim() + "...";
  };

  // --- TABS LOGIC ---
  $(".von-meta-tab").on("click", function () {
    $(".von-meta-tab").removeClass("active");
    $(this).addClass("active");
    $(".von-meta-content").removeClass("active");
    $("#" + $(this).data("tab")).addClass("active");
  });

  const updateSchemaFields = () => {
    const type = $("#vonseowp_schema_type").val();
    $("#row-review-rating").toggle(type === "Product" || type === "Review");
    $("input[name='vonseowp_rating_count']").closest("div").toggle(type === "Product");
    $("#row-reviewed-product").toggle(type === "Review");
  };
  $("#vonseowp_schema_type").on("change", updateSchemaFields);
  updateSchemaFields();

  // --- LIVE PREVIEW & COUNT LOGIC ---
  const updatePreview = () => {
    let title = $("#vonseowp_title").val() || $("#title").val() || "Post Title"; // Support WP classic editor title check
    let desc =
      $("#vonseowp_description").val() || vonseowp_metabox_data.analysis.waiting_desc || "Please provide a meta description.";
    let slug = $("#sample-permalink a").text() || window.location.href;

    // Clean
    desc = truncateAtWordBoundary(desc, 160);

    // Social Fallbacks
    let socTitle = $("#vonseowp_social_title").val() || title;
    let socDesc = $("#vonseowp_social_desc").val() || desc;

    // Update DOM
    $("#preview-title").text(title);
    $("#preview-desc").text(desc);

    // Social DOM
    $("#fb-preview-title").text(socTitle);
    $("#fb-preview-desc").text(socDesc);
    $("#tw-preview-title").text(socTitle);
    $("#tw-preview-desc").text(socDesc);

    // Char Counts & Bars
    updateBar("title", title.length, 60);
    updateBar("desc", desc.length, 160);

    // Run Analysis
    runAnalysis();
  };

  const updateBar = (type, current, max) => {
    let el = $("#" + type + "-bar");
    let txt = $("#" + type + "-len");
    let pct = (current / max) * 100;

    txt.text(current);
    el.css("width", Math.min(pct, 100) + "%");

    // "Smarter" Color Logic (Flexible Ranges)
    el.removeClass("good acceptable warn bad");

    if (current === 0) {
      el.addClass("bad");
    } else if (type === "title") {
      // Title: 30 - 65 is the sweet spot (Google cuts around 600px, roughly 60-70 chars)
      if (current >= 45 && current <= 63) el.addClass("good");
      else if (current >= 30 && current <= 70) el.addClass("acceptable");
      else if (current > 70) el.addClass("bad"); // Too Long
      else el.addClass("warn"); // Too Short
    } else if (type === "desc") {
      // Desc: 120 - 170 is the sweet spot
      if (current >= 120 && current <= 160) el.addClass("good");
      else if (current >= 90 && current <= 175) el.addClass("acceptable");
      else if (current > 175) el.addClass("bad"); // Too Long
      else el.addClass("warn"); // Too Short
    }
  };

  const getEditorContent = () => {
    let content = "";
    if (
      typeof wp !== "undefined" &&
      wp.data &&
      wp.data.select &&
      wp.data.select("core/editor")
    ) {
      try {
        content = wp.data
          .select("core/editor")
          .getEditedPostAttribute("content");
      } catch (e) { }
    }
    // Fallback for classic editor
    if (!content && typeof tinymce !== "undefined" && tinymce.activeEditor) {
      content = tinymce.activeEditor.getContent();
    }

    return content || $("#von-post-content-ref").val() || "";
  };

  const formatAnalysisMessage = (check) => {
    const messages = vonseowp_metabox_data.analysis;
    const meta = check.meta || {};

    switch (check.code) {
      case "title_keyword":
        return check.status === "good" ? messages.kw_found_title : messages.kw_missing_title;
      case "description_keyword":
        return check.status === "good" ? messages.kw_found_desc : messages.kw_missing_desc;
      case "first_paragraph_keyword":
        return check.status === "good" ? messages.kw_found_intro : messages.kw_missing_intro;
      case "keyword_density": {
        const density = Math.round((meta.density || 0) * 10) / 10;
        if (check.status === "good") return messages.density_good.replace("%s", density);
        if (check.status === "warn") return messages.density_warn.replace("%s", density);
        return messages.density_bad;
      }
      case "content_length":
        return check.status === "good"
          ? messages.content_good.replace("%d", meta.wordCount || 0)
          : messages.content_short.replace("%d", meta.wordCount || 0);
      case "title_length":
        if (check.status === "good") return messages.title_optimal;
        if (check.status === "warn") {
          return (meta.length || 0) < 45 ? messages.title_near_short : messages.title_near_long;
        }
        if ((meta.length || 0) === 0) return messages.title_missing;
        return (meta.length || 0) < 30 ? messages.title_too_short : messages.title_too_long;
      case "description_length":
        if (check.status === "good") return messages.desc_optimal;
        if (check.status === "warn") {
          return (meta.length || 0) < 120 ? messages.desc_near_short : messages.desc_near_long;
        }
        if ((meta.length || 0) === 0) return messages.desc_missing;
        return (meta.length || 0) < 90 ? messages.desc_too_short : messages.desc_too_long;
      case "heading_structure":
        if (check.status === "good") return messages.headings_good;
        if (check.status === "warn") return messages.headings_warn;
        return messages.headings_bad;
      case "image_alt":
        if (check.status === "good") return messages.images_good.replace("%d", meta.total || 0);
        if ((meta.total || 0) === 0) return messages.images_none;
        return messages.images_bad.replace("%d", meta.missingAlt || 0);
      case "link_presence":
        if (check.status === "good") return messages.links_good;
        return messages.links_warn;
      default:
        return messages.analyzing;
    }
  };

  // --- ANALYSIS ENGINE ---
  const runAnalysis = () => {
    const keyword = $("#vonseowp_keywords").val().split(",")[0].trim();
    const title = $("#vonseowp_title").val() || "";
    const desc = $("#vonseowp_description").val() || "";
    const analyzer = window.VonSEOWPAnalyzer;
    const $list = $("#von-analysis-list");

    if (!analyzer || typeof analyzer.analyze !== "function") {
      $list.empty().append(
        '<li class="item wait"><span class="dashicons dashicons-clock"></span> ' + vonseowp_metabox_data.analysis.analyzing + "</li>",
      );
      return;
    }

    const result = analyzer.analyze({
      keyword: keyword,
      title: title,
      description: desc,
      content: getEditorContent(),
      siteUrl: vonseowp_metabox_data.site_url || "",
    });

    // Render List
    $list.empty();

    if (result.waitingForKeyword) {
      $list.append(
        '<li class="item wait"><span class="dashicons dashicons-clock"></span> ' + vonseowp_metabox_data.analysis.waiting_keyword + "</li>",
      );
    } else {
      result.checks.forEach((i) => {
        let icon =
          i.status === "good"
            ? "yes-alt"
            : i.status === "warn"
              ? "warning"
              : "dismiss";
        let li = $("<li>").addClass("item " + i.status);
        li.append($("<span>").addClass("dashicons dashicons-" + icon));
        li.append(" " + formatAnalysisMessage(i));
        $list.append(li);
      });
    }

    // Render Score
    const score = result.score || 0;
    $("#von-analysis-score").text(score + "/100");
    $("#overall-score-circle").text(score);
    $("#opt-percent").text(score + "%");
    $("#optimization-level-bar")
      .css("width", score + "%")
      .removeClass("good warn bad");

    if (score >= 80) $("#optimization-level-bar").addClass("good");
    else if (score >= 50) $("#optimization-level-bar").addClass("warn");
    else $("#optimization-level-bar").addClass("bad");
  };

  // EVENTS
  $(
    "#vonseowp_title, #vonseowp_description, #vonseowp_keywords, #vonseowp_social_title, #vonseowp_social_desc",
  ).on("input keyup change", updatePreview);

  // Social Tabs
  $(".von-sp-tab").on("click", function () {
    $(".von-sp-tab").removeClass("active");
    $(this).addClass("active");
    $(".von-sp-content").removeClass("active");
    $("#" + $(this).data("target")).addClass("active");
  });

  // Watch WP Editor changes (Advanced)
  if (typeof wp !== "undefined" && wp.data && wp.data.subscribe) {
    let analysisTimer = null;
    wp.data.subscribe(function () {
      window.clearTimeout(analysisTimer);
      analysisTimer = window.setTimeout(runAnalysis, 400);
    });
  }

  // --- AI MAGIC LOGIC ---
  const getPostContent = () => getEditorContent();

  const getPostTitle = () => {
    let title = "";
    if (
      typeof wp !== "undefined" &&
      wp.data &&
      wp.data.select &&
      wp.data.select("core/editor")
    ) {
      try {
        title = wp.data.select("core/editor").getEditedPostAttribute("title");
      } catch (e) { }
    }
    return title || $("#title").val() || "";
  };

  const aiGenerate = (type, btn) => {
    const $btn = $(btn);
    const originalText = $btn.html();
    $btn
      .html(
        '<span class="dashicons dashicons-update von-ai-loading"></span> ' + vonseowp_metabox_data.ai.thinking,
      )
      .prop("disabled", true);

    setTimeout(() => {
      const content = getPostContent();

      // Create a virtual DOM to parse HTML
      const $html = $("<div>").html(content);

      // Text Cleaning Helper
      const cleanText = (txt) => txt.replace(/\s+/g, " ").trim();

      let result = "";

      if (type === "title") {
        // Strategy 0: Get actual Post Title (Primary Source)
        let candidate = getPostTitle();

        // Strategy 1: Look for Headings in content (Fallback)
        if (!candidate) {
          candidate =
            $html.find("h1").first().text() || $html.find("h2").first().text();
        }

        // Strategy 2: First strong sentence (Last Resort)
        if (!candidate) {
          const fullText = cleanText($html.text());
          const match = fullText.match(/^[^.!?]+[.!?]/); // Extract first sentence
          candidate = match ? match[0] : fullText.substring(0, 60);
        }

        result = cleanText(candidate);
      } else if (type === "desc") {
        // Strategy: Find a substantial paragraph that IS NOT the title/heading AND NOT metadata
        let titleText = cleanText($html.find("h1, h2").text());

        // Blacklist / Filter Patterns
        const metadataPhrases = [
          "written by",
          "author:",
          "compiled by",
          "prepared by",
          "photo source",
          "image source",
          "credit:",
          "source:",
          "published by",
          "edited by",
        ];

        $html.find("p").each(function () {
          let pText = cleanText($(this).text());
          let lowerText = pText.toLowerCase();

          // 1. Skip if too short
          if (pText.length < 50) return; // continue

          // 2. Skip if identical or contained in title
          if (titleText.includes(pText.substring(0, 20))) return;

          // 3. Skip if starts with separator-like chars
          if (/^[-=*_]{2,}/.test(pText)) return;

          // 4. Skip common "Key: Value" credit patterns (Generic)
          // Matches lines starting with "Key : Value" (e.g. "Author : Name", "Source : Site")
          if (/^[A-Za-z\s]+:\s*[A-Z]/.test(pText.substring(0, 30))) return;

          // 5. Skip if contains blacklist phrases (Metadata)
          if (metadataPhrases.some((phrase) => lowerText.includes(phrase)))
            return;

          // If passed all checks, this is our winner
          result = pText;
          return false; // Break loop
        });

        // Fallback: Use body text but try to strip metadata/title
        if (!result) {
          let fullText = cleanText($html.text());
          if (fullText.startsWith(titleText)) {
            fullText = fullText.substring(titleText.length).trim();
          }
          result = fullText;
        }

      } else if (type === "keywords") {
        // Cleaning & Density Analysis
        // Replace punctuation with spaces to avoid "Tehran/Washington" -> "tehranwashington"
        let text = cleanText($html.text())
          .toLowerCase()
          .replace(/[^\w\s-]/gi, " ");

        // Enhanced Stopwords List (English + Malay)
        const stopWords = [
          // English
          "this",
          "that",
          "with",
          "from",
          "have",
          "text",
          "word",
          "what",
          "when",
          "where",
          "your",
          "about",
          "more",
          "will",
          "make",
          "just",
          "should",
          "could",
          "content",
          "post",
          "page",
          "site",
          "website",
          "also",
          "into",
          "some",
          "these",
          "other",
          "than",
          "then",
          "very",
          "only",
          "most",
          "been",
          "were",
          "there",
          "their",
          "they",
          "them",
          "does",
          "even",
          "back",
          "down",
          "here",
          // Malay (Bahasa Melayu)
          "yang",
          "untuk",
          "kepada",
          "dengan",
          "dalam",
          "pada",
          "oleh",
          "bagi",
          "atau",
          "serta",
          "kerana",
          "tidak",
          "juga",
          "adalah",
          "bahawa",
          "tetapi",
          "seperti",
          "tersebut",
          "mereka",
          "daripada",
          "masih",
          "sedang",
          "akan",
          "hendak",
          "saya",
          "anda",
          "kami",
          "kita",
          "ini",
          "itu",
          "bila",
          "mana",
        ];

        let words = text
          .split(" ")
          .filter((w) => w.length > 3 && !stopWords.includes(w));
        let counts = {};
        words.forEach((w) => (counts[w] = (counts[w] || 0) + 1));

        // Dynamic Limit: 5 for short, 8 for medium, 10 for long content
        const wordCount = text.split(/\s+/).length;
        let limit = 5;
        if (wordCount > 300) limit = 8;
        if (wordCount > 600) limit = 10;

        result = Object.keys(counts)
          .sort((a, b) => counts[b] - counts[a])
          .slice(0, limit)
          .join(", ");
      }

      // Auto-inject Focus Keyword if missing (SEO Fix)
      const focusKw = $("#vonseowp_keywords").val().split(",")[0].trim();
      if (
        focusKw &&
        type !== "keywords" &&
        result &&
        !result.toLowerCase().includes(focusKw.toLowerCase())
      ) {
        if (type === "title") {
          // Ensure keyword is at the start for better SEO
          result = focusKw + " - " + result;
        } else if (type === "desc") {
          // Ensure keyword is included naturally
          result = focusKw + ": " + result;
        }
      }

      if (type === "title") result = truncateAtWordBoundary(result, 60);
      if (type === "desc") result = truncateAtWordBoundary(result, 160);

      if (type === "keywords")
        $("#vonseowp_keywords").val(result).trigger("input");
      else if (type === "title")
        $("#vonseowp_title").val(result).trigger("input");
      else if (type === "desc")
        $("#vonseowp_description").val(result).trigger("input");

      // Force immediate analysis
      runAnalysis();
      updatePreview();

      $btn.html(originalText).prop("disabled", false);
    }, 1000);
  };

  $("#von-ai-gen-title").on("click", function () {
    aiGenerate("title", this);
  });
  $("#von-ai-gen-desc").on("click", function () {
    aiGenerate("desc", this);
  });
  $("#von-ai-gen-keywords").on("click", function () {
    aiGenerate("keywords", this);
  });

  // --- COMPETITOR ANALYSIS LOGIC ---
  const scanCompetitor = () => {
    const url = $("#von-competitor-url").val();
    if (!url) {
      alert(vonseowp_metabox_data.ai.no_url);
      return;
    }

    const $btn = $("#von-scan-competitor");
    const originalHtml = $btn.html();
    $btn
      .html(
        '<span class="dashicons dashicons-update von-ai-loading"></span> ' + vonseowp_metabox_data.ai.scanning,
      )
      .prop("disabled", true);
    $("#von-competitor-results").hide();

    $.ajax({
      url: ajaxurl,
      type: "POST",
      data: {
        action: "vonseowp_scan_competitor",
        url: url,
        post_id: $("#post_ID").val(),
        nonce: $("#vonseowp_meta_box_nonce").val(),
      },
      success: function (response) {
        if (response.success) {
          renderCompetitorComparison(response.data);
        } else {
          alert("Error: " + response.data.message);
        }
      },
      error: function () {
        alert(vonseowp_metabox_data.ai.conn_err);
      },
      complete: function () {
        $btn.html(originalHtml).prop("disabled", false);
      },
    });
  };

  const renderCompetitorComparison = (comp) => {
    const $results = $("#von-competitor-results");

    // 1. Get User Metrics
    const content = getPostContent();
    const textOnly = $("<div>").html(content).text();
    const userWords = textOnly.split(/\s+/).filter((w) => w.length > 3).length;
    const userH1 = $("<div>").html(content).find("h1").length;
    const userH2 = $("<div>").html(content).find("h2").length;
    const userH3 = $("<div>").html(content).find("h3").length;
    const userImg = $("<div>").html(content).find("img").length;
    const userReading = runAnalysisAndGetReadingScore(textOnly); // Simplified for now

    // 2. Update Table
    updateMetricRow("word_count", userWords, comp.word_count);
    updateMetricRow(
      "h_count",
      userH1 + userH2 + userH3,
      comp.h1_count + comp.h2_count + comp.h3_count,
    );
    updateMetricRow("images", userImg, comp.image_count);
    updateMetricRow("reading_ease", userReading, comp.reading_ease);

    // SEO Math Score (Based on Word Count & Structure proximity)
    let userScore = parseInt($("#overall-score-circle").text()) || 0;
    let compScore = calculateCompScore(comp);
    updateMetricRow("seo_score", userScore, compScore);

    const $advice = $("#von-comp-advice");
    $advice.empty();
    $advice.append("<strong>" + vonseowp_metabox_data.ai.advice_prefix + "</strong> ");

    let msg = "";
    if (userWords < comp.word_count) {
      msg += vonseowp_metabox_data.ai.advice_short.replace("%d", comp.word_count - userWords) + " ";
    } else {
      msg += vonseowp_metabox_data.ai.advice_depth + " ";
    }

    if (userReading < comp.reading_ease) {
      msg += vonseowp_metabox_data.ai.advice_reading;
    }

    $advice.append($("<span>").text(msg));
    $results.fadeIn();
  };

  const updateMetricRow = (id, user, comp) => {
    const $row = $(`.competitor-table tr[data-metric="${id}"]`);
    $row.find(".val-user").text(user);
    $row.find(".val-comp").text(comp);

    const gap = user - comp;
    const $gapCell = $row.find(".val-gap");
    $gapCell.text((gap > 0 ? "+" : "") + gap).removeClass("good bad");

    if (id === "reading_ease" || id === "seo_score") {
      $gapCell.addClass(gap >= 0 ? "good" : "bad");
    } else {
      // For word counts etc, closer or more is usually better
      $gapCell.addClass(gap >= -50 ? "good" : "bad");
    }
  };

  const calculateCompScore = (c) => {
    let s = 50; // Starting point
    if (c.word_count > 600) s += 20;
    if (c.h1_count === 1) s += 10;
    if (c.image_count > 2) s += 10;
    if (c.reading_ease > 60) s += 10;
    return Math.min(100, s);
  };

  const runAnalysisAndGetReadingScore = (text) => {
    // Simplified FK approximation as in PHP
    const words = text.split(/\s+/).length;
    if (!words) return 0;
    const sentences = (text.match(/[.!?]/g) || []).length || 1;
    return Math.round(100 - words / sentences); // Very simplified for JS
  };

  // --- IMAGE UPLOAD LOGIC ---
  let frame;
  $("#von-upload-image").on("click", function (e) {
    e.preventDefault();

    if (frame) {
      frame.open();
      return;
    }

    frame = wp.media({
      title: "Select Social Image",
      button: { text: "Use this image" },
      multiple: false,
    });

    frame.on("select", function () {
      let attachment = frame.state().get("selection").first().toJSON();
      $("#vonseowp_image").val(attachment.url);
      $("#von-image-preview").attr("src", attachment.url).show();

      // Update Social Preview Images
      $("#fb-preview-img").css(
        "background-image",
        "url(" + attachment.url + ")",
      );
      $("#tw-preview-img").css(
        "background-image",
        "url(" + attachment.url + ")",
      );
    });

    frame.open();
  });

  $("#von-scan-competitor").on("click", scanCompetitor);

  // --- FAQ REPEATER LOGIC ---
  $("#von-add-faq").on("click", function () {
    const index = $("#von-faq-repeater .von-faq-row").length;
    const html = `
            <div class="von-faq-row" data-index="${index}">
                <input type="text" name="vonseowp_faq[${index}][q]" placeholder="${vonseowp_metabox_data.analysis.faq_q || "Question..."}" class="von-input-sm">
                <textarea name="vonseowp_faq[${index}][a]" placeholder="${vonseowp_metabox_data.analysis.faq_a || "Answer..."}" class="von-input-sm"></textarea>
                <button type="button" class="von-remove-faq">${vonseowp_metabox_data.analysis.remove || "Remove"}</button>
            </div>
        `;
    $("#von-faq-repeater").append(html);
  });

  $(document).on("click", ".von-remove-faq", function () {
    $(this).closest(".von-faq-row").remove();
    // Re-index
    $("#von-faq-repeater .von-faq-row").each(function (i) {
      $(this).attr("data-index", i);
      $(this).find("input").attr("name", `vonseowp_faq[${i}][q]`);
      $(this).find("textarea").attr("name", `vonseowp_faq[${i}][a]`);
    });
  });

  // --- SCHEMA TESTER ---
  $("#von-test-schema").on("click", function () {
    const previewUrl = encodeURIComponent(
      $("#sample-permalink a").text() || window.location.href,
    );
    window.open(
      `https://search.google.com/test/rich-results?url=${previewUrl}`,
      "_blank",
    );
  });

  $("#von-validate-schema").on("click", function () {
    const previewUrl = encodeURIComponent(
      $("#sample-permalink a").text() || window.location.href,
    );
    window.open(`https://validator.schema.org/#url=${previewUrl}`, "_blank");
  });

  // Init
  setTimeout(() => {
    updatePreview();
    // Init social images on load
    let initImg = $("#vonseowp_image").val();
    if (initImg) {
      $("#fb-preview-img").css("background-image", "url(" + initImg + ")");
      $("#tw-preview-img").css("background-image", "url(" + initImg + ")");
    }
  }, 1000); // Wait for editor to load
});
