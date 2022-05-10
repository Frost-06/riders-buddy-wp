jQuery(document).ready(function() {
    custom_api_wp_GetColumn();
});

function custom_api_wp_GetColumn() {
    jQuery('#SelectedColumn').multiselect({
        includeSelectAllOption: true,
        enableFiltering: true
    });
}

jQuery("#CountryList li").click(function() {
    jQuery(this).toggleClass('country active'); ///User selected value...****

});


function custom_api_wp_CustomText() {
    var check = document.getElementById("ColumnParam").value;
    if (check == "custom") {
        document.getElementById("Param").style.visibility = "visible";
    }
}

function custom_api_wp_GetTbColumn() {

    document.getElementById("method_name_initial").value = document.getElementById("MethodName").value;
    document.getElementById("api_name_initial").value = document.getElementById("ApiName").value;
    document.getElementById("table_name_initial").value = document.getElementById("select-table").value;
    document.getElementById("SubmitForm1").click();

}

function custom_api_wp_ShowData() {

    var ApiName = document.getElementById("ApiName").value;
    var MethodName = document.getElementById("MethodName").value;
    var SelectedTable = document.getElementById("select-table").value;

    var SelectedCondtion = document.getElementById("ColumnCondition").value;
    var SelectedCoulmn = jQuery('#SelectedColumn').val();
    var SelectedParameter = document.getElementById("ColumnParam").value;
    var ConditionColumn = document.getElementById("OnColumn").value;
    document.getElementById("Selectedcolumn11").value = jQuery('#SelectedColumn').val();



    var query;
    if ((SelectedCondtion == "no condition")) {
        if (MethodName == "GET") {
            query = "Select ";
        }
        query += SelectedCoulmn;
        query += " from ";
        query += SelectedTable;
        document.getElementById("QueryVal").value = query;
    } else {
        if (MethodName == "GET") {
            query = "Select ";
        }
        query += SelectedCoulmn;
        query += " from ";
        query += SelectedTable;
        query += " WHERE ";
        query += ConditionColumn + " ";
        query += SelectedCondtion + " ";
        query += SelectedParameter;

        document.getElementById("QueryVal").value = query;
    }
}


jQuery("#contact_us_phone").intlTelInput();

function custom_api_wp_valid_query(f) {
    !(/^[a-zA-Z?,.\(\)\/@ 0-9]*$/).test(f.value) ? f.value = f.value.replace(
        /[^a-zA-Z?,.\(\)\/@ 0-9]/, '') : null;
}

function add_dynamic_externalapi_ui(Headers, Body, RequestType) {
    if (Headers.length > 1) {
        for (var i = 1; i < Headers.length; i++) {
            var HeaderArray = Headers[i].split(":");
            add_header(HeaderArray[0], HeaderArray[1]);
        }
    }
    if (RequestType == "x-www-form-urlencode") {
        Body = decodeURIComponent(Body);
        var bodyarr = Body.split("&");
        console.log(bodyarr);
        if (bodyarr.length > 1) {
            for (var i = 1; i < bodyarr.length; i++) {
                var RequestBodyArray = bodyarr[i].split("=");

                add_request_body_param(RequestBodyArray[0], RequestBodyArray[1]);
            }
        }
    } else {
        document.getElementById("RequestBodyJson").value = JSON.stringify(Body);
        document.getElementById("DivRequestBodyKey").style.display = "none";
        document.getElementById("DivRequestBodyValue").style.display = "none";
        document.getElementById("DivRequestBodyAddButton").style.display = "none";
        document.getElementById("RequestBodyJsonTextArea").style.display = "block";
    }
}

var HeaderCount = 1;

function add_header(key, value) {
    var div = document.createElement("div");
    div.style.width = "100%";
    div.style.marginLeft = "12px";
    div.id = "ExternalApiContainer" + HeaderCount;

    html =
        "<br><div class=row >" +
        "<div class=col-md-2>" +
        '<label class="mo_custom_api_labels" style="visibility:hidden"> Headers</label>' +
        " </div>" +
        "<div class=col-md-3 >" +
        '<input type="text" class="mo_custom_api_custom_field" id="ExternalHeaderKey' +
        HeaderCount +
        '"  name="ExternalHeaderKey' +
        HeaderCount +
        '" placeholder="Enter Key" value=' +
        key +
        ">" +
        "</div>" +
        "<div class=col-md-3 style='margin-left:-4px;'>" +
        '<input type="text" class="mo_custom_api_custom_field" id="ExternalHeaderValue' +
        HeaderCount +
        '"  name="ExternalHeaderValue' +
        HeaderCount +
        '" placeholder="Enter Value" value=' +
        value +
        ">" +
        "</div>" +
        "<div class=col-md-3>" +
        '<button class ="mo_custom_api_contact_us_submit_btn" style="width:50px;margin-top:5px;margin-left:-4px;color:white;" onclick="remove_header(' +
        HeaderCount +
        ')"><strong style="font-size:15px;font-weight:900;">-</strong></button>' +
        "</div>" +
        "</div>";
    div.innerHTML = html;
    document.getElementById("ExternalApiHeaders").appendChild(div);
    document.getElementById("ExternalHeaderCount").value = HeaderCount;
    HeaderCount++;
}

function remove_header(id) {
    var getid = "ExternalApiContainer" + id;
    var div = document.getElementById(getid);

    if (div) {
        div.parentNode.removeChild(div);
    }
}

var RequestBodyCount = 1;

function add_request_body_param(key, value) {
    var div = document.createElement("div");
    div.style.width = "100%";
    div.style.marginLeft = "5px";
    div.id = "ExternalApiRequestBodyContainer" + RequestBodyCount;

    html =
        "<br><div class=row >" +
        "<div class=col-md-2>" +
        " </div>" +
        '<div class="col-md-3">' +
        "</div>" +
        "<div class=col-md-3 >" +
        '<input type="text" class="mo_custom_api_custom_field" id="RequestBodyKey' +
        RequestBodyCount +
        '"  name="RequestBodyKey' +
        RequestBodyCount +
        '" placeholder="Enter Key" value=' +
        key +
        ">" +
        "</div>" +
        "<div class=col-md-3 style='margin-left:-7px;' >" +
        '<input type="text" class="mo_custom_api_custom_field" id="RequestBodyValue' +
        RequestBodyCount +
        '"  name="RequestBodyValue' +
        RequestBodyCount +
        '" placeholder="Enter Value" value=' +
        value +
        ">" +
        "</div>" +
        "<div class=col-md-1 style='margin-left: -36px;'>" +
        // <i class="fa fa-minus"></i>
        '<button class ="mo_custom_api_contact_us_submit_btn" style="width:50px;margin-top:5px;" id="RequestBodyRemove' +
        RequestBodyCount +
        '" onclick="remove_requestbody(' +
        RequestBodyCount +
        ')"><strong style="font-size:15px;font-weight:900;">-</strong></button>' +
        "</div>" +
        "</div>";

    div.innerHTML = html;
    document.getElementById("ExternalApiBody").appendChild(div);
    document.getElementById("ExternalResponseBodyCount").value = RequestBodyCount;
    RequestBodyCount++;
}

function remove_requestbody(id) {
    var getid = "ExternalApiRequestBodyContainer" + id;
    var div = document.getElementById(getid);

    if (div) {
        div.parentNode.removeChild(div);
    }
}

function RequestBodyTypeOnChange() {
    var RequestType = document.getElementById("RequestBodyType").value;

    if (RequestType == "x-www-form-urlencode") {
        document.getElementById("RequestBodyJsonTextArea").style.display = "none";
        document.getElementById("DivRequestBodyKey").style.display = "block";
        document.getElementById("DivRequestBodyValue").style.display = "block";
        document.getElementById("DivRequestBodyAddButton").style.display = "block";

        var BodyParams = document.getElementById("ExternalResponseBodyCount").value;

        for (var j = 1; j <= BodyParams; j++) {
            if (document.getElementById("RequestBodyKey" + j) !== null) {
                document.getElementById("RequestBodyKey" + j).style.display = "block";
            }
            if (document.getElementById("RequestBodyValue" + j) !== null) {
                document.getElementById("RequestBodyValue" + j).style.display = "block";
            }
            if (document.getElementById("RequestBodyRemove" + j) !== null) {
                document.getElementById("RequestBodyRemove" + j).style.display =
                    "block";
            }
        }

        // document.getElementById("ExternalApiBodyJson").style.visibility = "hidden";
        // document.getElementById("ExternalApiBody").style.visibility = "visible";
        //     document.getElementById("ExternalApiHtml").innerHTML = ' <div class=row id="ExternalApiBody">' +
        //     '<div class=col-md-3>'+
        //         '<label class="mo_custom_api_labels"> Request Body</label>'+
        //    ' </div>'+

        //     '<div class=col-md-3>'+
        //         '<select class="mo_custom_api_SelectColumn" id="RequestBodyType" name="RequestBodyType" onchange="RequestBodyTypeOnChange()" >'+
        //        '<option value="x-www-form-urlencode" selected>x-www-form-urlencode</option>'+
        //         '<option value="json">JSON</option>'

        //         '</select>'+
        //     '</div>'+

        //     '<div class=col-md-3>'+
        //    '<input type="text" id="RequestBodyKey"  name="RequestBodyKey" placeholder="Enter Key">'+

        //     '</div>'+

        //     '<div class=col-md-2>'+
        //     '<input type="text" id="RequestBodyValue"  name="RequestBodyValue" placeholder="Enter Value">'+

        //    '</div>'+

        //     '<div class=col-md-1>'+
        //     '<button class ="btn btn-primary" id="RequestBodyAddButton" onclick="add_request_body_param()"><i class="fa fa-plus"></i></button>'+
        //     '</div>'+

        // '</div>';
    } else if (RequestType == "json") {
        document.getElementById("DivRequestBodyKey").style.display = "none";
        document.getElementById("DivRequestBodyValue").style.display = "none";
        document.getElementById("DivRequestBodyAddButton").style.display = "none";

        var BodyParams = document.getElementById("ExternalResponseBodyCount").value;

        for (var j = 1; j <= BodyParams; j++) {
            if (document.getElementById("RequestBodyKey" + j) !== null) {
                document.getElementById("RequestBodyKey" + j).style.display = "none";
            }
            if (document.getElementById("RequestBodyValue" + j) !== null) {
                document.getElementById("RequestBodyValue" + j).style.display = "none";
            }
            if (document.getElementById("RequestBodyRemove" + j) !== null) {
                document.getElementById("RequestBodyRemove" + j).style.display = "none";
            }
        }

        document.getElementById("RequestBodyJsonTextArea").style.display = "block";

        // document.getElementById("ExternalApiBody").style.visibility = "hidden";
        // document.getElementById("ExternalApiBodyJson").style.visibility = "visible";

        //         document.getElementById("ExternalApiHtml").innerHTML = ' <div class=row id="ExternalApiBody">' +
        //         '<div class=col-md-3>'+
        //             '<label class="mo_custom_api_labels"> Request Body</label>'+
        //        ' </div>'+

        //         '<div class=col-md-3>'+
        //             '<select class="mo_custom_api_SelectColumn" id="RequestBodyType" name="RequestBodyType" onchange="RequestBodyTypeOnChange()" >'+
        //            '<option value="x-www-form-urlencode">x-www-form-urlencode</option>'+
        //             '<option value="json" >JSON</option>'

        //             '</select>'+
        //         '</div>';
        //         document.getElementById("ExternalApiHtml").innerHTML = document.getElementById("ExternalApiHtml").innerHTML +
        //         '<div class=col-md-3>'+
        //         '<label class="mo_custom_api_labels"> Request Body</label>'+
        //    ' </div>'+

        //     '</div>';
    }
}

function saveexternalapi() {
    document.getElementById("selected_column_all").value =
        jQuery("#SelectedColumn").val();
}

function editexternalapi(rowIndexOfGridview) {
    var selected = rowIndexOfGridview.parentNode.parentNode;
    var ApiName = selected.cells[0].innerHTML;

    var SendUrl1 =
        window.location.href + "&action=editexternal&apiname=" + ApiName;

    location.replace(SendUrl1);
}

function deleteExternalapi(rowIndexOfGridview) {
    var selected = rowIndexOfGridview.parentNode.parentNode;
    var ApiName = selected.cells[0].innerHTML;

    var SendUrl =
        window.location.href + "&action=deleteexternal&apiname=" + ApiName;

    location.replace(SendUrl);
}

function custom_api_wp_delete(rowIndexOfGridview) {

    var selected = rowIndexOfGridview.parentNode.parentNode;
    var ApiName = selected.cells[0].innerHTML;


    var SendUrl = window.location.href + "&action=delete&api=" + ApiName;

    location.replace(SendUrl);

}

function custom_api_wp_edit(rowIndexOfGridview) {
    var selected = rowIndexOfGridview.parentNode.parentNode;
    var ApiName = selected.cells[0].innerHTML;

    var SendUrl1 = window.location.href + "&action=edit&api=" + ApiName;

    location.replace(SendUrl1);
}

function custom_api_wp_view(rowIndexOfGridview) {
    var selected = rowIndexOfGridview.parentNode.parentNode;
    var ApiName = selected.cells[0].innerHTML;

    var SendUrl1 = window.location.href + "&action=view&api=" + ApiName;

    location.replace(SendUrl1);
}

function custom_api_wp_delete_sql(rowIndexOfGridview) {
    var selected = rowIndexOfGridview.parentNode.parentNode;
    var ApiName = selected.cells[0].innerHTML;

    var SendUrl = window.location.href + "&action=deletesql&apisql=" + ApiName;

    location.replace(SendUrl);
}

function custom_api_wp_edit_sql(rowIndexOfGridview) {
    var selected = rowIndexOfGridview.parentNode.parentNode;
    var ApiName = selected.cells[0].innerHTML;

    var SendUrl1 = window.location.href + "&action=sqledit&apisql=" + ApiName;

    location.replace(SendUrl1);
}

function custom_api_wp_view_sql(rowIndexOfGridview) {
    var selected = rowIndexOfGridview.parentNode.parentNode;
    var ApiName = selected.cells[0].innerHTML;

    var SendUrl1 = window.location.href + "&action=view&api=" + ApiName;

    location.replace(SendUrl1);
}

function change_description(sel) {
    if (sel.value == "GET")
        document.getElementById("method_description").innerHTML =
        "Fetch data via API";
    else if (sel.value == "POST")
        document.getElementById("method_description").innerHTML =
        "Create/Add data via API";
    else if (sel.value == "PUT")
        document.getElementById("method_description").innerHTML =
        "Modify data values via API";
    else if (sel.value == "Delete")
        document.getElementById("method_description").innerHTML =
        "Delete existing data via API";
}