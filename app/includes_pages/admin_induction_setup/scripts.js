var crud_url ='includes_pages/admin_induction/crud.php';
function Loadtab(tab_id) {
    $.ajax({
        type: "POST",
        url: "includes_pages/admin_induction/content.php",
        data: { tab_id: tab_id }, 
        success: function (response) {
           console.log("LoadTab:" +tab_id) 
            $('#tab_body').html(response);
        },
        error: function (xhr, status, error) {
            // Handle error response
            console.error("Error receiving data:", error);
            var errorMessage = xhr.responseText;
        }
    });
}
function EditInduction(id) { 
    $.ajax({
        type: "POST",
        url: "includes_pages/admin_induction/content.php",
        data: { tab_id: 'edit_induction',id: id }, // Ensure 'tab_id' is sent correctly
        success: function (response) {

            $('#tab_body').html(response);
			
			getInduction(id)
        },
        error: function (xhr, status, error) {
            handleError(xhr, status, error) 
        }
	
    });
function getInduction(induction_id) {
    var formData = {
        action: 'read_induction',
		induction_id: induction_id,
    };
	var jsonData = JSON.stringify(formData);
    $.ajax({
        url: crud_url,
        type: 'POST',
        data: jsonData,
        contentType: "application/json",
        success: function(response) {
                var data = response[0];
			console.log(response)
                $("#induction_description").val(data.description);


				
				},
				error: function(xhr, status, error) {
				   handleError(xhr, status, error)
				}
    });
	
}
}
function AddInductionModal() {
	$('#inductionModal').modal('show');
}
function AddInduction() {
    var formData = {
        'action': 'add_induction',
        'description': $("#induction_description").val()
    };
    var jsonData = JSON.stringify(formData);
    $.ajax({
        url: crud_url,
        type: 'POST',
        data: jsonData,
        contentType: "application/json",
        success: function (response) {
            try {
                var parsedResponse = JSON.parse(response);
                if (parsedResponse.success) {
                    handleSuccess(parsedResponse.message);
                    $('#inductionModal').modal('hide');
                    Loadtab('induction_list');
                } else {
                    handleError(null, null, parsedResponse.message);
                }
            } catch (e) {
                handleError(null, null, "Error parsing JSON response: " + e.message);
            }
        },
        error: function (xhr, status, error) {
            // Check if xhr is not null before calling handleError
            if (xhr) {
                handleError(xhr, status, error);
            } else {
                // Provide a default error message if xhr is null
                handleError(null, status, "Network or server error occurred.");
            }
        }
    });
}

document.getElementById('questionForm').addEventListener('submit', function(event) {
    const radios = document.querySelectorAll('input[type="radio"][name="answer"]');
    const isAnswerSelected = Array.from(radios).some(radio => radio.checked);
    if (!isAnswerSelected) {
        event.preventDefault();
        alert('Please select an answer.');
    }
});

$(document).ready(function() {
   //GetCompanyId();
	Loadtab('induction_list')
	
});



	