(function () {
	'use strict';

	// Progressive enhancement of the "Older posts" link: fetch the next page
	// and append its articles in place. Anything missing (no link, no first
	// article, an old browser) leaves ordinary pagination alone.
	var list = document.querySelector('.post-list');
	var nav = list && list.querySelector('.posts-nav');
	var older = nav && nav.querySelector('.posts-nav-older');
	var link = older && older.querySelector('a');
	var first = list && list.querySelector('article');
	if (!link || !first || !('IntersectionObserver' in window) || !window.fetch || !window.DOMParser) {
		return;
	}

	// Live status line under the navigation; role=status announces changes.
	var status = document.createElement('p');
	status.className = 'post-list-status';
	status.setAttribute('role', 'status');
	nav.insertAdjacentElement('afterend', status);

	// markers: the first article of each loaded page and that page's URL, so
	// the address bar can follow the reader (see sync()). The HTML appended
	// here is trusted same-origin site output; this script is not a sanitizer.
	var markers = [{ el: first, url: location.href }];
	var current = location.href;
	var loading = false;
	var failed = false;

	// Start loading when the navigation comes within 800px of the viewport.
	var observer = new IntersectionObserver(function (entries) {
		for (var i = 0; i < entries.length; i++) {
			if (entries[i].isIntersecting) {
				load();
				return;
			}
		}
	}, { rootMargin: '0px 0px 800px 0px' });
	observer.observe(nav);

	link.addEventListener('click', function (e) {
		// Plain left-clicks load in place. Modified clicks, and clicks while a
		// load is running or after one failed, keep their normal behavior.
		if (failed || loading || e.button !== 0 || e.metaKey || e.ctrlKey || e.shiftKey || e.altKey) {
			return;
		}
		e.preventDefault();
		load();
	});

	// On the editor site core can print block styles outside the articles
	// (style[id] and link[id] in the head) that a later page's blocks need.
	// Bring over what the fetched page has and this one lacks, in the original
	// order. Any change of meaning (a different sheet URL, a style id reused
	// for different rules, new rules for an already styled selector) throws,
	// which falls back to ordinary navigation. The static site has one
	// compiled stylesheet, so nothing here runs there.
	function loadStyles(doc) {
		var changes = [];
		var styles = Array.from(doc.querySelectorAll('style[id], link[rel="stylesheet"][id]'));
		styles.forEach(function (source, index) {
			var existing = document.getElementById(source.id);
			if (!existing) {
				// Insert before the first later sheet that already exists here, so
				// the cascade order of the source document is preserved.
				var before = styles.slice(index + 1).map(function (style) { return document.getElementById(style.id); }).find(Boolean);
				changes.push({ node: source.cloneNode(true), before: before });
				return;
			}
			if (source.tagName !== existing.tagName) {
				throw new Error('Stylesheet changed');
			}
			if (source.tagName === 'LINK') {
				if (source.href !== existing.href) {
					throw new Error('Stylesheet changed');
				}
				return;
			}
			if (source.textContent === existing.textContent) {
				return;
			}
			var sheet = new CSSStyleSheet();
			sheet.replaceSync(source.textContent);
			var rules = Array.from(existing.sheet.cssRules);
			var extra = [];
			Array.from(sheet.cssRules).forEach(function (rule) {
				if (rules.some(function (old) { return old.cssText === rule.cssText; })) {
					return;
				}
				// A new rule for a selector that already has one would change the
				// existing layout; refuse rather than guess.
				if (!rule.selectorText || rules.some(function (old) { return old.selectorText === rule.selectorText; })) {
					throw new Error('Conflicting block styles');
				}
				extra.push(rule.cssText);
			});
			if (extra.length) {
				changes.push({ node: existing, css: extra.join('\n') });
			}
		});
		return Promise.all(changes.map(function (change) {
			function insert() {
				if (change.before) {
					change.before.parentNode.insertBefore(change.node, change.before);
				} else {
					document.head.appendChild(change.node);
				}
			}
			if (change.css) {
				change.node.textContent += '\n' + change.css;
			} else if (change.node.tagName === 'STYLE') {
				insert();
			} else {
				// A linked sheet must finish loading before the articles appear; a
				// timeout or load error falls back to ordinary navigation.
				return new Promise(function (resolve, reject) {
					var timer = setTimeout(function () { done(new Error('Stylesheet timed out')); }, 10000);
					function done(error) {
						clearTimeout(timer);
						change.node.onload = change.node.onerror = null;
						if (error) {
							change.node.remove();
							reject(error);
						} else {
							resolve();
						}
					}
					change.node.onload = function () { done(); };
					change.node.onerror = function () { done(new Error('Stylesheet failed')); };
					insert();
				});
			}
		}));
	}

	function load() {
		if (loading || failed) {
			return;
		}
		loading = true;
		var url = link.href;
		list.setAttribute('aria-busy', 'true');
		status.textContent = 'Loading older posts…';
		fetch(url, { mode: 'same-origin', credentials: 'same-origin' })
			.then(function (r) {
				if (!r.ok) {
					throw new Error('HTTP ' + r.status);
				}
				return r.text();
			})
			.then(function (html) {
				var doc = new DOMParser().parseFromString(html, 'text/html');
				if (!doc.querySelector('.post-list > article')) {
					throw new Error('No post list in response');
				}
				return loadStyles(doc).then(function () { return doc; });
			})
			.then(function (doc) {
				var articles = doc.querySelectorAll('.post-list > article');
				var next = doc.querySelector('.posts-nav-older a');
				var frag = document.createDocumentFragment();
				var added = 0;
				for (var i = 0; i < articles.length; i++) {
					var a = articles[i];
					// Skip an article already on the page: publishing moves page
					// boundaries, so consecutive pages can overlap.
					if (a.id && document.getElementById(a.id)) {
						continue;
					}
					if (!added) {
						markers.push({ el: a, url: url });
					}
					frag.appendChild(document.adoptNode(a));
					added++;
				}
				list.insertBefore(frag, nav);
				status.textContent = '';
				if (next && next.href !== url) {
					link.href = next.href;
					// Re-observe so the navigation is checked again at its new position;
					// if it is still within range the next page loads at once.
					observer.unobserve(nav);
					observer.observe(nav);
				} else {
					finish();
				}
			})
			.catch(function () {
				failed = true;
				observer.disconnect();
				status.textContent = 'Couldn’t load older posts here; use the link.';
			})
			.then(function () {
				loading = false;
				list.removeAttribute('aria-busy');
			});
	}

	// No next page: drop the Older link, and the navigation if nothing is left.
	function finish() {
		observer.disconnect();
		older.remove();
		if (!nav.querySelector('a')) {
			nav.remove();
		}
		status.textContent = 'No older posts.';
	}

	// Keep the URL on the page whose first article has crossed the middle of
	// the viewport, so reload and Back land near the same content. Throttled
	// to one pass per frame; Safari throws when replaceState runs too often.
	var ticking = false;
	function sync() {
		ticking = false;
		var line = window.innerHeight / 2;
		var url = markers[0].url;
		for (var i = 1; i < markers.length; i++) {
			if (markers[i].el.getBoundingClientRect().top > line) {
				break;
			}
			url = markers[i].url;
		}
		if (url !== current) {
			current = url;
			try {
				history.replaceState(history.state, '', url);
			} catch (e) {}
		}
	}
	window.addEventListener('scroll', function () {
		if (!ticking) {
			ticking = true;
			requestAnimationFrame(sync);
		}
	}, { passive: true });
})();
