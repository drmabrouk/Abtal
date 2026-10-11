jQuery(document).ready(function ($) {
  // Mobile sidebar toggle
  $('#spMobileToggle').on('click', function () {
    $('.sp-sidebar-floating').toggleClass('open');
  });

  // Floating label initial check & dynamic listener
  function updateFloatingLabels() {
    $('.sp-floating-input, .sp-floating-select').each(function () {
      if ($(this).val() && $(this).val().toString().trim() !== '') {
        $(this).addClass('has-value');
      } else {
        $(this).removeClass('has-value');
      }
    });
  }

  window.spUpdateFloatingLabels = updateFloatingLabels;

  updateFloatingLabels();

  $(document).on('change input blur focus', '.sp-floating-input, .sp-floating-select', function () {
    updateFloatingLabels();
  });

  $(document).on('reset', 'form', function () {
    setTimeout(updateFloatingLabels, 50);
  });

  // Reusable Modal Open/Close Helpers
  window.spOpenModal = function (modalId) {
    $('#' + modalId).css('display', 'flex').addClass('active open');
    updateFloatingLabels();
  };

  window.spCloseModal = function (modalId) {
    $('#' + modalId).css('display', 'none').removeClass('active open');
  };

  $(document).on('click', '.sp-modal-close, .sp-modal-overlay, .sp-modal', function (e) {
    if (e.target === this) {
      $(this).css('display', 'none').removeClass('active open');
    }
  });

  // Button Loading State Helper
  window.spSetButtonLoading = function (btnElement, isLoading, originalText) {
    var $btn = $(btnElement);
    if (isLoading) {
      $btn.data('orig-text', originalText || $btn.html());
      $btn.prop('disabled', true).css('opacity', '0.7').html('Processing...');
    } else {
      $btn.prop('disabled', false).css('opacity', '1').html($btn.data('orig-text') || originalText);
    }
  };

  // Universal Centered Circular Spinner HTML
  window.spSpinnerHtml = '<div class="sp-centered-spinner" style="display: flex; justify-content: center; align-items: center; padding: 48px; width: 100%;"><div class="sp-spinner-circle" style="width: 38px; height: 38px; border: 3px solid #e2e8f0; border-top-color: #0f172a; border-radius: 50%; animation: spSpin 0.7s linear infinite;"></div></div>';

  // Add spinning keyframe animation if not already present
  if (!$('#spSpinnerStyle').length) {
    $('head').append('<style id="spSpinnerStyle">@keyframes spSpin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }</style>');
  }

  // Universal Instant Search Engine with Debounce & Stale Response Handling
  function initInstantSearch(inputSelector, targetContainerSelector, actionName, extraFieldsFn) {
    var $input = $(inputSelector);
    if (!$input.length) return;

    var debounceTimer = null;
    var requestCounter = 0;

    $input.on('keyup input change', function () {
      clearTimeout(debounceTimer);
      var currentSearch = $input.val();

      debounceTimer = setTimeout(function () {
        var currentReqId = ++requestCounter;
        var $target = $(targetContainerSelector);

        if ($target.length) {
          $target.html(window.spSpinnerHtml);
        }

        var postData = {
          action: actionName,
          nonce: sportedia_vars.nonce,
          search: currentSearch
        };

        if (typeof extraFieldsFn === 'function') {
          $.extend(postData, extraFieldsFn());
        }

        $.post(sportedia_vars.ajax_url, postData, function (response) {
          // Stale response guard: ignore if a newer search request was initiated
          if (currentReqId !== requestCounter) return;

          if (response.success && response.data && response.data.html) {
            $target.html(response.data.html);
          } else {
            $target.html('<div class="sp-card" style="text-align: center; color: var(--sp-text-muted); padding: 48px;"><h3>No matching records found</h3><p>Try refining your search terms or filters.</p></div>');
          }
        }).fail(function () {
          if (currentReqId !== requestCounter) return;
          $target.html('<div class="sp-card" style="text-align: center; color: #dc2626; padding: 32px;"><h3>Unable to load search results</h3><p>Please check your network connection and try again.</p></div>');
        });
      }, 250);
    });
  }

  // Initialize Instant Search for all main Sportedia sections
  initInstantSearch('input[name="search"][placeholder*="name, ID"]', '#users_container', 'sportedia_search_users', function () {
    return {
      role_filter: $('select[name="role_filter"]').val() || '',
      branch_filter: $('select[name="branch_filter"]').val() || 0
    };
  });

  initInstantSearch('#sp_filter_search', '#subscriptions_container', 'sportedia_search_subscriptions', function () {
    return {
      branch_filter: $('#sp_filter_branch').val() || 0,
      status_filter: $('#sp_filter_status').val() || ''
    };
  });

  initInstantSearch('input[name="search"][placeholder*="program"]', '#programs_container', 'sportedia_search_programs', function () {
    return {
      branch_filter: $('select[name="branch_filter"]').val() || 0
    };
  });

  initInstantSearch('input[name="search"][placeholder*="branch"]', '#branches_container', 'sportedia_search_branches', function () {
    return {};
  });

  // Attendance Instant Filter & Search
  var attSearchInput = $('input[name="search"], input[name="att_date"]');
  if ($('input[name="att_date"]').length && $('table.sp-table tbody').length) {
    var attTimer = null;
    var attReqCounter = 0;

    $(document).on('keyup input change', 'input[name="att_date"], select[name="branch_filter"], select[name="program_filter"]', function () {
      clearTimeout(attTimer);
      attTimer = setTimeout(function () {
        var reqId = ++attReqCounter;
        var $tbody = $('table.sp-table tbody');
        $tbody.html('<tr><td colspan="9">' + window.spSpinnerHtml + '</td></tr>');

        $.post(sportedia_vars.ajax_url, {
          action: 'sportedia_search_attendance',
          nonce: sportedia_vars.nonce,
          att_date: $('input[name="att_date"]').val(),
          branch_filter: $('select[name="branch_filter"]').val() || 0,
          program_filter: $('select[name="program_filter"]').val() || 0
        }, function (res) {
          if (reqId !== attReqCounter) return;
          if (res.success && res.data && res.data.html) {
            $tbody.html(res.data.html);
          } else {
            $tbody.html('<tr><td colspan="9" style="text-align: center; padding: 32px; color: var(--sp-text-muted);">No attendance records found.</td></tr>');
          }
        });
      }, 250);
    });
  }
});
