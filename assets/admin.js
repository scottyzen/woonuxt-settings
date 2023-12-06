jQuery(document).ready(function ($) {
  const globalAttributes = $(".global_attribute_table");
  let uniqueId = Math.random().toString(36).slice(2, 11);

  // product_attributes is an object that contains the product attributes

  // deploy-button FROM build_hook
  const buildUrl = $("#build_url");
  $("#deploy-button").click(function (e) {
    e.preventDefault();
    $.ajax({
      url: buildUrl.val(),
      type: "POST",
      success(data) {
        alert("Build triggered successfully");
      },
    });
  });

  // add global attribute
  $(".add_global_attribute").click(function (e) {
    e.preventDefault();
    console.log("add global attribute");
    const newAttribute = `
	<tr>
		<td>
			<input type="text" class="flex-1" name="woonuxt_options[global_attributes][${uniqueId}][label]" value="" placeholder="e.g. Filter by Color"/>
        </td>
        <td>
			<select name="woonuxt_options[global_attributes][${uniqueId}][slug]" required>
				${Object.keys(product_attributes).map((key) => {
          return `<option value="pa_${product_attributes[key].attribute_name}">${product_attributes[key].attribute_label}</option>`;
        })}
			</select>
		</td>
		<td>
			<input type="checkbox" name="woonuxt_options[global_attributes][${uniqueId}][showCount]" value="1" />
		</td>
		<td>
			<input type="checkbox" name="woonuxt_options[global_attributes][${uniqueId}][hideEmpty]"value="1" />
		</td>
		<td>
			<input type="checkbox" name="woonuxt_options[global_attributes][${uniqueId}][openByDefault]" value="1"  />
		</td>
		<td>
			<div class="text-right row-actions">
				<a href="#" class="text-danger remove_global_attribute">Delete</a> |
				<a href="#" title="Move Up" class="text-primary move_global_attribute_up">▲</a> |
				<a href="#" title="Move Down" class="text-primary move_global_attribute_down">▼</a>
			</div>
		</td>
	</tr>
`;

    globalAttributes.find("tbody").append(newAttribute);

    uniqueId = Math.random().toString(36).slice(2, 11);
  });

  // remove global attribute
  $(document).on("click", ".remove_global_attribute", function (e) {
    e.preventDefault();
    $(this).closest("tr").remove();
  });

  // move global attribute
  $(document).on("click", ".move_global_attribute_up", function (e) {
    e.preventDefault();
    const currentRow = $(this).closest("tr");
    const prevRow = currentRow.prev();
    if (prevRow.length) {
      currentRow.insertBefore(prevRow);
    }
  });

  $(document).on("click", ".move_global_attribute_down", function (e) {
    e.preventDefault();
    const currentRow = $(this).closest("tr");
    const nextRow = currentRow.next();
    if (nextRow.length) {
      currentRow.insertAfter(nextRow);
    }
  });

  // Handle color picker
  $("#primary-color-setting input").on("change, input", function () {
    $("#woonuxt_options\\[primary_color\\]").val($(this).val());
    $("#color-preview").css("background-color", $(this).val());
    $("#primary_color_picker").val($(this).val());
  });
});
