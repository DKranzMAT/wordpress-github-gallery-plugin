document.addEventListener('DOMContentLoaded', function () {
  document.querySelectorAll('.ghrg-wrap').forEach(function (wrap) {
    var gridView = wrap.querySelector('.ghrg-gallery-grid');
    var listView = wrap.querySelector('.ghrg-gallery-list');
    var viewBtns = wrap.querySelectorAll('.ghrg-view-btn');
    var searchInput = wrap.querySelector('.ghrg-search');
    var langFilter = wrap.querySelector('.ghrg-filter-language');
    var sortSelect = wrap.querySelector('.ghrg-sort');
    var emptyFiltered = wrap.querySelector('.ghrg-empty-filtered');

    // View toggle
    viewBtns.forEach(function (btn) {
      btn.addEventListener('click', function () {
        var view = btn.getAttribute('data-view');
        viewBtns.forEach(function (b) { b.classList.remove('active'); });
        btn.classList.add('active');

        if (view === 'grid') {
          gridView.classList.remove('ghrg-hidden');
          listView.classList.add('ghrg-hidden');
        } else {
          listView.classList.remove('ghrg-hidden');
          gridView.classList.add('ghrg-hidden');
        }
        applyFilters();
      });
    });

    // Filtering
    function applyFilters() {
      var query = searchInput ? searchInput.value.trim().toLowerCase() : '';
      var lang = langFilter ? langFilter.value : '';
      var visibleCount = 0;

      var activeView = gridView.classList.contains('ghrg-hidden') ? listView : gridView;
      var items = activeView.querySelectorAll('.ghrg-repo-card, .ghrg-repo-row');

      items.forEach(function (item) {
        var name = item.getAttribute('data-name') || '';
        var desc = item.getAttribute('data-description') || '';
        var itemLang = item.getAttribute('data-language') || '';

        var matchesQuery = !query || name.indexOf(query) !== -1 || desc.indexOf(query) !== -1;
        var matchesLang = !lang || itemLang === lang;

        if (matchesQuery && matchesLang) {
          item.style.display = '';
          visibleCount++;
        } else {
          item.style.display = 'none';
        }
      });

      if (emptyFiltered) {
        if (visibleCount === 0) {
          emptyFiltered.classList.remove('ghrg-hidden');
        } else {
          emptyFiltered.classList.add('ghrg-hidden');
        }
      }
    }

    if (searchInput) {
      searchInput.addEventListener('input', applyFilters);
    }
    if (langFilter) {
      langFilter.addEventListener('change', applyFilters);
    }

    // Sorting (client-side re-order of both views)
    if (sortSelect) {
      sortSelect.addEventListener('change', function () {
        var sortBy = sortSelect.value;
        [gridView, listView].forEach(function (container) {
          var items = Array.prototype.slice.call(
            container.querySelectorAll('.ghrg-repo-card, .ghrg-repo-row')
          );

          items.sort(function (a, b) {
            if (sortBy === 'name') {
              return a.getAttribute('data-name').localeCompare(b.getAttribute('data-name'));
            }
            if (sortBy === 'stars') {
              var starsA = parseInt((a.querySelector('.ghrg-repo-stars, .ghrg-row-meta span') || {}).textContent.replace(/\D/g, '')) || 0;
              var starsB = parseInt((b.querySelector('.ghrg-repo-stars, .ghrg-row-meta span') || {}).textContent.replace(/\D/g, '')) || 0;
              return starsB - starsA;
            }
            // updated / created: keep original server-side order (already sorted)
            return 0;
          });

          items.forEach(function (item) {
            container.appendChild(item);
          });
        });

        applyFilters();
      });
    }

    applyFilters();
  });
});