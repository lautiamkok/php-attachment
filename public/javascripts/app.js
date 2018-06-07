'use strict'

// Import node modules.
import 'babel-polyfill'
import DocReady from 'es6-docready'
import $ from 'jquery'
import 'jquery-ui-bundle'
import Foundation from 'foundation-sites'
import autosize from 'autosize'
import AOS from 'aos'

// Must wait until DOM is ready before initiating the modules.
DocReady(async () => {
  console.log("DOM is ready. Let's party")

  // Initiate foundation.
  // Must do it after Vue has rendered the view.
  $(document).foundation()

  // Attach the change event listener to change the label of all input[type=file] elements
  // https://foundation.zurb.com/forum/posts/50259-adding-filename-feedback-to-stylized-foundation-file-upload-buttons
  var els = document.querySelectorAll('input[type=file]')
  var i
  for (i = 0; i < els.length; i++) {
    els[i].addEventListener('change', function () {
      var context = $(this).parents('.group')
      if (parseInt(this.files.length) > 0) {
        context.find('.button-clear').removeClass('hide')
      } else {
        context.find('.button-clear').addClass('hide')
      }
    })
  }

  // Clear input file.
  $('.button-clear').on('click', function () {
    var $this = $(this)
    var context = $this.parents('.group')
    context.find('input[type=file]').val('')
    $this.addClass('hide')
    return false
  })

  // Scroll up.
  var position = $('main').position()
  var scrollUp = $('.button-arrow-up')
  window.addEventListener('scroll', function () {
    var height = $(window).scrollTop()
    if (height > position.top) {
      scrollUp.fadeIn('slow')
      // scrollUp.fadeTo("slow", 1)
    } else {
      scrollUp.fadeOut('slow')
      // scrollUp.fadeTo("slow", 0)
    }
  })
  scrollUp.click(function () {
    // Must add 'html' to the target for Firefox.
    $('body, html').animate({ scrollTop: 0 },
      600,
      'easeOutExpo',
    function () {
      //
    })
    return false
  })

  // Submit message form.
  // http://foundation.zurb.com/forum/posts/37267-foundation-6-abide-trigger-without-submit-button
  var form = $('form.form-submit')
  form.bind('submit', function (e) {
    $(this).find('.callout').addClass('hide')

    e.preventDefault()
    console.log('submit intercepted')
    return false
  })
  form.bind('forminvalid.zf.abide', function (e, target) {
    console.log('form is invalid')
  })

  form.bind('formvalid.zf.abide', function (e, target) {
    console.log('form is valid')

    // Show the loader.
    $('.row-loader').removeClass('hide')

    var formdata = false
    if (window.FormData) {
      formdata = new FormData()
    }

    // Iterate through the elements and append it to the form data, with the right type.
    target.find('input, textarea').not('input[type=file]').each(function () {
      // For an example:
      // fd.append('id', 1000)
      formdata.append(this.name, this.value)
    })

    // Get file data.
    var els = document.querySelectorAll('input[type=file]')
    var i = 0
    for (i = 0; i < els.length; i++) {
      // Make sure the file has content before append it to the form object.
      var file = els[i]

      if (file.files.length > 0) {
        var fileData = file.files[0]

        // Only process image files.
        if (!fileData.type.match('image.*')) {
          continue
        }

        // Appends the currently selected File of an <input type="file" id="file"> element the FormData instance
        // which is later to be sent as multipart/form-data XHR request body
        formdata.append('sender-attachments[]', fileData)
      }
    }

    $.ajax({
      type: 'POST',
      url: target.attr('action'),
      data: formdata,
      processData: false, // tell jQuery not to process the data
      contentType: false, // tell jQuery not to set contentType
      success: function (responseData, textStatus, jqXHR) {
        // Hide the loader.
        $('.row-loader').addClass('hide')

        // Sent OK.
        if (responseData.status === 200) {
          console.log(responseData)

          // Clear the form.
          target.find('input[type=text], input[type=email], input[type=file], textarea').val('')

          // Show the success message.
          target.find('.response-message').text(responseData.message)
          target.find('.success.callout').removeClass('hide')

          // Hide the button.
          target.find('.button-clear').addClass('hide')
        }
      },
      error: function (jqXHR, textStatus, errorThrown) {
        console.log(jqXHR.statusText)

        // Hide the loader.
        $('.row-loader').addClass('hide')

        // Parse text.
        var responseText = $.parseJSON(jqXHR.responseText)

        // Make sure the status is 400.
        if (jqXHR.status === 400) {
          console.log(responseText)

          target.find('.response-message').text(responseText.message)
          target.find('.warning.callout').removeClass('hide')
        }
      }
    })
  })

  // Get Z Foundation media query screen size.
  // http://foundation.zurb.com/sites/docs/javascript-utilities.html#mediaquery
  function getZFcurrentMediaQuery () {
    return Foundation.MediaQuery.current
  }

  window.addEventListener('resize', () => {
    var current = getZFcurrentMediaQuery()
    console.log('Screen size: ' + current)
  })

  // https://stackoverflow.com/questions/10328665/how-to-detect-browser-minimize-and-maximize-state-in-javascript
  document.addEventListener('visibilitychange', () => {
    console.log(document.hidden, document.visibilityState)
  }, false)

  // Textarea autosize.
  // http://www.jacklmoore.com/autosize/
  // https://github.com/jackmoore/autosize
  // https://www.npmjs.com/package/autosize
  // from a jQuery collection
  autosize($('textarea'))

  // AOS scroll reveal.
  // http://michalsnik.github.io/aos/
  // https://css-tricks.com/aos-css-driven-scroll-animation-library/
  AOS.init({
    duration: 1200
  })

  // Refresh/ re-init aos on scroll.
  document.addEventListener('scroll', (event) => {
    AOS.init({
      duration: 1200
    })
    // if (event.target.id === 'idOfUl') { // or any other filtering condition
    //     console.log('scrolling', event.target)
    // }
  }, true /* Capture event */)
})
