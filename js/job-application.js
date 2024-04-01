jQuery(document).ready(function ($) {
    $('#application_preview').hide();
    $(document).on('submit', '.job-application-form', function (event) {
        event.preventDefault();

        var form = $(this);
        var formData = form.serialize(); // Serialize form data
        formData += '&action=submit_job_application'; // Append action to serialized data

        $.ajax({
            type: 'POST',
            url: myAjax.ajaxurl,
            data: formData,
            dataType: 'json',
            success: function (response) {
                console.log("Response: ", response);
                form.trigger('reset');
                showJobPreviews(response)
            },
            error: function () {
                console.log('An error occurred. Please try again later.');
            }
        });
    });

    function showJobPreviews(application) {
        // create a preview when a submitted.
        $('#application_preview').show();
        const previewContainer = `
        <div class="job-preview">
        <p>Applicant Name: ${application.applicant_Name}, Applicant Email: ${application.applicant_Email}</p>
        <p>Message: ${application.message}</p>
        </div>`

        // Append the preview container to the page
        $('#application_preview').append(previewContainer);
    }
});
