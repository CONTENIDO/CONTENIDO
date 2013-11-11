var left_bottom = Con.getFrame('left_bottom');
if (left_bottom.get_registered_parameters) {
    left_bottom.location.href = left_bottom.location.href + left_bottom.get_registered_parameters();
} else {
    left_bottom.location.href = left_bottom.location.href;
}