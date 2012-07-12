if (parent.parent.frames['left'].frames['left_bottom'].get_registered_parameters) {
    parent.parent.frames['left'].frames['left_bottom'].location.href = parent.parent.frames['left'].frames['left_bottom'].location.href + parent.parent.frames['left'].frames['left_bottom'].get_registered_parameters();
} else {
    parent.parent.frames['left'].frames['left_bottom'].location.href = parent.parent.frames['left'].frames['left_bottom'].location.href;
}