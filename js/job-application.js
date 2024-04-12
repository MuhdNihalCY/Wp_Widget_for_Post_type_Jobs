jQuery(document).ready(function ($) {
    $('#application_preview').hide();
    $(document).on('submit', '.job-application-form', function (event) {
        event.preventDefault();
        var form = $(this);
        var formData = form.serialize();
        formData += '&action=submit_job_application'; 

        $.ajax({
            type: 'POST',
            url: ajax_url.ajaxurl,
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
        <h2>Application Preview</h2>
        <div class="job-preview">
        <p>Applicant Name: ${application.applicant_Name}, Applicant Email: ${application.applicant_Email}</p>
        <p>Message: ${application.message}</p>
        </div>`
        $('#application_preview').html(previewContainer);
    }

    // admin application delete
    $(document).on('click', '.application_del_btn', (event) => {
        var job_id = $(event.target).data('job-id');
        var application_index = $(event.target).data('application-index');
        var applicant_name = $(event.target).data('applicant-name');
        var applicant_email = $(event.target).data('applicant-email');

        var targetDiv = event.target.parentNode;

        var formData = {    
            'job_id': job_id,
            'application_index': application_index,
            'applicant_name': applicant_name,
            'applicant_email': applicant_email,
            'action': 'delete_job_application'
        }

        $.ajax({
            type: 'POST',
            url: ajax_url.ajaxurl,
            data: formData,
            dataType: 'json',
            success: (response) => {
                console.log("Delete Respose: ", response);
                targetDiv.style.display = "none";
            },
            error: (err) => {
                alert("Ajax error called");
            }
        })
    })
});
