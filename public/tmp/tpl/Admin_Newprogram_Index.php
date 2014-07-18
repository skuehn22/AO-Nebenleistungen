<?php if(!class_exists('raintpl')){exit;}?><script type="text/javascript">

    var gridExistingCompany;
    var companySearch;
    var searchButton;
    var paginatorCompany;
    var jsonStoreGridCompany;

    var formNewProgram;

    var companyId;
    var companyName;
    var companyCity;

    var condition_aktiv = 3;
    var condition_passiv = 2;
    var condition_new = 1;

	Ext.onReady(function(){



    });

function showProgrammeDerFirma(){
    if(!checkCompanySelect())
        return;

    window.open('/admin/datensatz/index/company/' + companyName + '/city/' + companyCity + '/','_parent');
}

function checkCompanySelect(){
    var selectModelCompanyGrid = gridExistingCompany.getSelectionModel();
    if(!selectModelCompanyGrid.hasSelection()){
        showMsgBox('Bitte Firma auswählen');

        return false;
    }

    var data = selectModelCompanyGrid.getSelected().data;

    companyId = data.id;
    companyName = data.company;
    companyCity = data.city;

    return true;
}

function showFormNewProgram(){
    if(!checkCompanySelect())
        return;

    Ext.getCmp('formNewProgram').getForm().reset();

    if(formNewProgram){
        childWindow.add(formNewProgram);
    }

    childWindow.setTitle('neues Programm');
    childWindow.doLayout();
    childWindow.show();
}

function saveNewProgram(button, event){

    formNewProgram.getForm().submit({
        params: {
            id: companyId
        },
        success: function(form, action){
            showMsgBox('neues Programm angelegt');
            formNewProgram.getForm().reset();
            childWindow.hide();

        },
        failure: function(form, action){
            showMsgBox('Fehler beim eintragen neues Programm');
        }
    });
    
}

function rendererAktivPassivIcon(val){
    var icon = '';
    
    if(val == condition_new)
        icon = "<img src='/buttons/exclamation.png'>";
    else if(val == condition_passiv)
        icon = "<img src='/buttons/cancel.png'>";
    else if(val == condition_aktiv)
        icon = "<img src='/buttons/accept.png'>";

    return icon;
}

function sendQuestionFindCompany(){
    companySearch = Ext.getCmp('companySearch');
    if(companySearch.getValue().length < 3){
        showMsgBox('Bitte Firmenname eingeben');
        companySearch.setValue('');

        return;
    }

    jsonStoreGridCompany.setBaseParam('searchField', companySearch.getValue());
    jsonStoreGridCompany.load();
}

</script>

<script type="text/javascript" src="/tpl/Admin_Newprogram_IndexGridExistingCompany.js"></script>
<script type="text/javascript" src="/tpl/Admin_Newprogram_IndexFormNewProgram.js"></script>

<div class='span-32' id='info'>
    <div class="blockDescription span-15">
    <h3 style="color: blue">anlegen neuer Programme</h3>
    <br>
    Anlegen eines neuen Programmdatensatzes.<br>
    Zum anlegen eines neuen Programmes wird eine bereits vorhandene Firma<br>
    ausgewählt. Die Programmbezeichnung und die Stadt müssen eingetragen werden.
    </div>

</div>
<div class="span-30">&nbsp;</div>
<div class='span-13' id='panelExistingCompany'></div>
