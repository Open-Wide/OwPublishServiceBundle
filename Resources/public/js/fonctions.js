/**
 * Created by lsabatier on 20/04/15.
 */
$(document).ready(function () {

    $('.service_subscription_checkbox').change(function () {
        tab = $(this).attr('name').split('_');
        
        var userId = tab[1];
        var serviceLinkId = tab[2];
        var locationIdServiceFolder = tab[3];
        var status = 0;

        if($(this).is(":checked")) {
            status = 1;
        }

        $.ajax({
            url: '/service/subscription/'+userId+'/'+serviceLinkId+'/'+status+'/'+locationIdServiceFolder,
            type: 'GET',
            dataType: 'html',
            
            success: function (code_html, statut) {
                
            },
            error: function (resultat, statut, erreur) {
                alert("Erreur : La modification n'a pas été prise en compte.");
            }
        });
    });
});