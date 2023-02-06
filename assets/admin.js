jQuery(document).ready(function ($) {
	const globalAttributes = $('.global_attribute_table');
	let uniqueId = Math.random().toString(36).substr(2, 9);

	// deploy-button FROM build_hook
	const buildUrl = $('#build_url');
	$('#deploy-button').click(function (e) {
		e.preventDefault();
		$.ajax({
			url: buildUrl.val(),
			type: 'POST',
			success: function (data) {
				alert('Build triggered successfully');
			},
		});
	});

	// add global attribute
	$('.add_global_attribute').click(function (e) {
		e.preventDefault();
		console.log('add global attribute');
		const newAttribute = `
                            <tr>
                                <td>
                                    <input type="text" 
                                    class="flex-1" 
                                    name="woonuxt_options[global_attributes][${uniqueId}][label]" 
                                    value="" 
                                    placeholder="e.g. Filter by Color"
                                    />
                                </td>
                                <td>
                                    <select name="woonuxt_options[global_attributes][${uniqueId}][slug]" required>
                                        <?php foreach ( $product_attributes as $attribute ) : ?>
                                            <option value="" disabled selected>Select Attribute</option>
                                            <option value="pa_<?php echo $attribute->attribute_name; ?>">
                                                <?php echo $attribute->attribute_label; ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </td>
                                <td>
                                    <input type="checkbox" 
                                    name="woonuxt_options[global_attributes][${uniqueId}][showCount]" 
                                    value="1" 
                                    />
                                </td>
                                <td>
                                    <input type="checkbox" 
                                    name="woonuxt_options[global_attributes][${uniqueId}][hideEmpty]"
                                    value="1"
                                    />
                                </td>
                                <td>
                                    <input type="checkbox" 
                                    name="woonuxt_options[global_attributes][${uniqueId}][openByDefault]" 
                                    value="1" 
                                    />
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

		globalAttributes.find('tbody').append(newAttribute);

		uniqueId = Math.random().toString(36).substr(2, 9);
	});

	// remove global attribute
	$(document).on('click', '.remove_global_attribute', function (e) {
		e.preventDefault();
		console.log('remove');
		// find parent tr
		$(this).closest('tr').remove();
	});

	// move global attribute
	$(document).on('click', '.move_global_attribute_up', function (e) {
		e.preventDefault();
		console.log('move up');
		const currentRow = $(this).closest('tr');
		const prevRow = currentRow.prev();
		if (prevRow.length) {
			currentRow.insertBefore(prevRow);
		}
	});

	$(document).on('click', '.move_global_attribute_down', function (e) {
		e.preventDefault();
		console.log('move down');
		const currentRow = $(this).closest('tr');
		const nextRow = currentRow.next();
		if (nextRow.length) {
			currentRow.insertAfter(nextRow);
		}
	});
});
