(function (root, factory) {
  const api = factory();

  if (typeof module !== "undefined" && module.exports) {
    module.exports = api;
  }

  root.VonSEOWPAnalyzer = api;
})(typeof window !== "undefined" ? window : this, function () {
  const SCORE_RULES = {
    title_keyword: 12,
    description_keyword: 12,
    first_paragraph_keyword: 10,
    keyword_density: 12,
    content_length: 8,
    title_length: 10,
    description_length: 10,
    heading_structure: 10,
    image_alt: 8,
    link_presence: 8,
  };

  const decodeEntities = (value) =>
    String(value || "")
      .replace(/&nbsp;/gi, " ")
      .replace(/&lt;/gi, "<")
      .replace(/&gt;/gi, ">")
      .replace(/&quot;/gi, '"')
      .replace(/&#39;/gi, "'")
      .replace(/&amp;/gi, "&");

  const normalizeExtractedText = (value) =>
    String(value || "")
      .replace(/\s+/g, " ")
      .trim();

  const findTagEnd = (html, start) => {
    let quote = "";

    for (let index = start + 1; index < html.length; index += 1) {
      const character = html.charAt(index);

      if (quote) {
        if (character === quote) {
          quote = "";
        }
        continue;
      }

      if (character === '"' || character === "'") {
        quote = character;
      } else if (character === ">") {
        return index;
      }
    }

    return -1;
  };

  const parseTag = (tag) => {
    let index = 1;
    let closing = false;

    while (/\s/.test(tag.charAt(index))) {
      index += 1;
    }
    if (tag.charAt(index) === "/") {
      closing = true;
      index += 1;
    }
    while (/\s/.test(tag.charAt(index))) {
      index += 1;
    }

    const start = index;
    while (/[a-z0-9:-]/i.test(tag.charAt(index))) {
      index += 1;
    }

    return {
      closing,
      name: tag.slice(start, index).toLowerCase(),
    };
  };

  const stripHtmlWithoutDom = (html) => {
    let output = "";
    let ignoredTag = "";
    let index = 0;

    while (index < html.length) {
      if (ignoredTag) {
        const closingPrefix = `</${ignoredTag}`;
        const possibleClosingTag = html.slice(index, index + closingPrefix.length);
        const boundary = html.charAt(index + closingPrefix.length);

        if (
          possibleClosingTag.toLowerCase() === closingPrefix &&
          (!boundary || /[\s/>]/.test(boundary))
        ) {
          const tagEnd = findTagEnd(html, index);
          if (tagEnd < 0) {
            break;
          }
          ignoredTag = "";
          output += " ";
          index = tagEnd + 1;
          continue;
        }

        index += 1;
        continue;
      }

      if (html.charAt(index) !== "<") {
        output += html.charAt(index);
        index += 1;
        continue;
      }

      const tagEnd = findTagEnd(html, index);
      if (tagEnd < 0) {
        output += " " + html.slice(index + 1);
        break;
      }

      const tag = parseTag(html.slice(index, tagEnd + 1));
      if (tag.name === "script" || tag.name === "style") {
        if (!tag.closing) {
          ignoredTag = tag.name;
        }
      } else {
        output += " ";
      }

      index = tagEnd + 1;
    }

    return normalizeExtractedText(output);
  };

  const stripHtml = (html) => {
    const decoded = decodeEntities(html);

    if (typeof DOMParser === "function") {
      const documentNode = new DOMParser().parseFromString(decoded, "text/html");
      documentNode.querySelectorAll("script, style").forEach((node) => node.remove());
      return normalizeExtractedText(documentNode.body ? documentNode.body.textContent : "");
    }

    return stripHtmlWithoutDom(decoded);
  };

  const wordsFromText = (text) => {
    const matches = String(text || "")
      .toLowerCase()
      .match(/[a-z0-9]+(?:[-'][a-z0-9]+)?/g);
    return matches || [];
  };

  const normalizeText = (text) => wordsFromText(text).join(" ");

  const escapeRegex = (value) =>
    String(value).replace(/[.*+?^${}()|[\]\\]/g, "\\$&");

  const countKeyword = (text, keyword) => {
    const normalizedText = normalizeText(text);
    const normalizedKeyword = normalizeText(keyword);

    if (!normalizedText || !normalizedKeyword) {
      return 0;
    }

    const pattern = new RegExp(
      "(^|[^a-z0-9])" + escapeRegex(normalizedKeyword) + "(?=$|[^a-z0-9])",
      "g",
    );
    return (normalizedText.match(pattern) || []).length;
  };

  const hasKeyword = (text, keyword) => countKeyword(text, keyword) > 0;

  const extractHeadings = (html) => {
    const headings = [];
    const pattern = /<h([1-6])\b[^>]*>([\s\S]*?)<\/h\1>/gi;
    let match;

    while ((match = pattern.exec(String(html || ""))) !== null) {
      headings.push({
        level: parseInt(match[1], 10),
        text: stripHtml(match[2]),
      });
    }

    return headings;
  };

  const extractImages = (html) => {
    const images = String(html || "").match(/<img\b[^>]*>/gi) || [];
    const missingAlt = images.filter((tag) => {
      const altMatch = tag.match(/\salt\s*=\s*(?:"([^"]*)"|'([^']*)'|([^\s>]+))/i);
      const altText = altMatch ? altMatch[1] || altMatch[2] || altMatch[3] || "" : "";
      return altText.trim() === "";
    }).length;

    return {
      total: images.length,
      missingAlt,
    };
  };

  const getHost = (url) => {
    const match = String(url || "").match(/^https?:\/\/([^/?#]+)/i);
    return match ? match[1].toLowerCase() : "";
  };

  const extractLinks = (html, siteUrl) => {
    const links = {
      internal: 0,
      external: 0,
    };
    const siteHost = getHost(siteUrl);
    const pattern = /<a\b[^>]*\shref\s*=\s*(?:"([^"]*)"|'([^']*)'|([^\s>]+))/gi;
    let match;

    while ((match = pattern.exec(String(html || ""))) !== null) {
      const href = (match[1] || match[2] || match[3] || "").trim();

      if (!href || href.charAt(0) === "#" || /^(mailto|tel):/i.test(href)) {
        continue;
      }

      const host = getHost(href);
      if (!host || (siteHost && host === siteHost)) {
        links.internal++;
      } else {
        links.external++;
      }
    }

    return links;
  };

  const getFirstParagraph = (html) => {
    const match = String(html || "").match(/<p\b[^>]*>([\s\S]*?)<\/p>/i);
    return match ? stripHtml(match[1]) : "";
  };

  const makeCheck = (code, status, score, meta) => ({
    code,
    status,
    score,
    meta: meta || {},
  });

  const scoreTitleLength = (title) => {
    const length = String(title || "").trim().length;
    if (length >= 45 && length <= 63) {
      return makeCheck("title_length", "good", SCORE_RULES.title_length, { length });
    }
    if (length >= 30 && length <= 70) {
      return makeCheck("title_length", "warn", 7, { length });
    }
    return makeCheck("title_length", "bad", length ? 2 : 0, { length });
  };

  const scoreDescriptionLength = (description) => {
    const length = String(description || "").trim().length;
    if (length >= 120 && length <= 160) {
      return makeCheck("description_length", "good", SCORE_RULES.description_length, { length });
    }
    if (length >= 90 && length <= 175) {
      return makeCheck("description_length", "warn", 7, { length });
    }
    return makeCheck("description_length", "bad", length ? 2 : 0, { length });
  };

  const scoreContentLength = (wordCount) => {
    if (wordCount >= 300) {
      return makeCheck("content_length", "good", SCORE_RULES.content_length, { wordCount });
    }
    if (wordCount >= 120) {
      return makeCheck("content_length", "warn", 5, { wordCount });
    }
    return makeCheck("content_length", "warn", 3, { wordCount });
  };

  const scoreKeywordDensity = (text, keyword, wordCount) => {
    const keywordWordCount = Math.max(1, wordsFromText(keyword).length);
    const occurrences = countKeyword(text, keyword);
    const density = wordCount ? (occurrences * keywordWordCount * 100) / wordCount : 0;

    if (density >= 0.5 && density <= 3) {
      return makeCheck("keyword_density", "good", SCORE_RULES.keyword_density, {
        density,
        occurrences,
      });
    }
    if (density > 0 && density <= 5) {
      return makeCheck("keyword_density", "warn", 7, { density, occurrences });
    }
    return makeCheck("keyword_density", "bad", 0, { density, occurrences });
  };

  const scoreHeadings = (headings, keyword) => {
    const h1Count = headings.filter((heading) => heading.level === 1).length;
    const h2PlusCount = headings.filter((heading) => heading.level >= 2).length;
    const emptyCount = headings.filter((heading) => heading.text.trim() === "").length;
    const keywordInHeading = headings.some((heading) => hasKeyword(heading.text, keyword));

    if (h1Count >= 1 && h2PlusCount >= 1 && emptyCount === 0 && keywordInHeading) {
      return makeCheck("heading_structure", "good", SCORE_RULES.heading_structure, {
        h1Count,
        h2PlusCount,
        emptyCount,
      });
    }
    if (headings.length > 0 && emptyCount === 0) {
      return makeCheck("heading_structure", "warn", 5, { h1Count, h2PlusCount, emptyCount });
    }
    return makeCheck("heading_structure", "bad", 0, { h1Count, h2PlusCount, emptyCount });
  };

  const scoreImages = (images) => {
    if (images.total === 0) {
      return makeCheck("image_alt", "warn", 4, images);
    }
    if (images.missingAlt === 0) {
      return makeCheck("image_alt", "good", SCORE_RULES.image_alt, images);
    }
    return makeCheck("image_alt", "bad", 0, images);
  };

  const scoreLinks = (links) => {
    if (links.internal > 0 && links.external > 0) {
      return makeCheck("link_presence", "good", SCORE_RULES.link_presence, links);
    }
    if (links.internal > 0 || links.external > 0) {
      return makeCheck("link_presence", "warn", 4, links);
    }
    return makeCheck("link_presence", "warn", 2, links);
  };

  const analyze = (input) => {
    const data = input || {};
    const keyword = String(data.keyword || "").trim();

    if (!keyword) {
      return {
        score: 0,
        waitingForKeyword: true,
        checks: [],
      };
    }

    const title = String(data.title || "");
    const description = String(data.description || "");
    const content = String(data.content || "");
    const text = stripHtml(content);
    const wordCount = wordsFromText(text).length;
    const headings = extractHeadings(content);
    const images = extractImages(content);
    const links = extractLinks(content, data.siteUrl || "");
    const firstParagraph = getFirstParagraph(content) || text;

    const checks = [
      makeCheck(
        "title_keyword",
        hasKeyword(title, keyword) ? "good" : "bad",
        hasKeyword(title, keyword) ? SCORE_RULES.title_keyword : 0,
      ),
      makeCheck(
        "description_keyword",
        hasKeyword(description, keyword) ? "good" : "bad",
        hasKeyword(description, keyword) ? SCORE_RULES.description_keyword : 0,
      ),
      makeCheck(
        "first_paragraph_keyword",
        hasKeyword(firstParagraph, keyword) ? "good" : "bad",
        hasKeyword(firstParagraph, keyword) ? SCORE_RULES.first_paragraph_keyword : 0,
      ),
      scoreKeywordDensity(text, keyword, wordCount),
      scoreContentLength(wordCount),
      scoreTitleLength(title),
      scoreDescriptionLength(description),
      scoreHeadings(headings, keyword),
      scoreImages(images),
      scoreLinks(links),
    ];

    return {
      score: Math.min(
        100,
        checks.reduce((total, check) => total + check.score, 0),
      ),
      waitingForKeyword: false,
      checks,
      metrics: {
        wordCount,
        headings: headings.length,
        images: images.total,
        missingAlt: images.missingAlt,
        internalLinks: links.internal,
        externalLinks: links.external,
      },
    };
  };

  return {
    analyze,
    stripHtml,
    wordsFromText,
    countKeyword,
  };
});
