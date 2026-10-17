;(function ($) {
  // Every plugin bundling this library loads its own copy of this file, and WordPress prints that
  // copy's bitappsTelemetryDeactivateModal('<slug>') call right after it. So each modal runs the
  // version of this code that shipped with its markup.
  window.bitappsTelemetryDeactivateModal = function (slug) {
    $(function () {
      const $wrapper = $('.bitapps-dm-wrapper[data-slug="' + slug + '"]')

      if (!$wrapper.length) return

      // Looked up on each use, because searching the plugins list replaces its rows.
      const linkSelector = '#the-list [data-slug="' + slug + '"] span.deactivate a'
      const $dialog = $wrapper.find('.bitapps-dm-dialog')
      const $form = $wrapper.find('.bitapps-dm-form')
      let deactivateUrl = ''
      let mouseDownOnBackdrop = false
      let sending = false

      function deactivate() {
        window.location.href = deactivateUrl
      }

      function closeOnEscape(e) {
        if (e.key === 'Escape') hideModal()
      }

      function showModal() {
        $wrapper.show()
        $dialog.trigger('focus')
        $(document).on('keyup', closeOnEscape)
      }

      function hideModal() {
        $wrapper.hide()
        $(document).off('keyup', closeOnEscape)
        $(linkSelector).trigger('focus')
      }

      $(document).on('click', linkSelector, function (e) {
        e.preventDefault()
        deactivateUrl = $(this).attr('href')
        showModal()
      })

      $wrapper.find('.bitapps-dm-close-svg').on('click', hideModal)

      // Close on a click that starts and ends on the backdrop, not on text selected inside the
      // dialog and released outside it.
      $wrapper.on('mousedown', e => {
        mouseDownOnBackdrop = e.target === $wrapper[0]
      })
      $wrapper.on('click', e => {
        if (mouseDownOnBackdrop && e.target === $wrapper[0]) hideModal()
      })

      $wrapper.find('.bitapps-dm-form-skip').on('click', deactivate)

      $form.on('submit', e => {
        e.preventDefault()
        if (sending) return
        sending = true
        $wrapper.find('.bitapps-dm-form-submit').addClass('bitapps-dm-loading')

        // Feedback is best effort: deactivating never waits for the telemetry server, and a
        // beacon is still delivered after the page navigates away.
        const data = $form.serialize()

        if (navigator.sendBeacon && navigator.sendBeacon(ajaxurl, new URLSearchParams(data))) {
          deactivate()
        } else {
          $.post(ajaxurl, data).always(deactivate)
        }
      })
    })
  }
})(jQuery)
