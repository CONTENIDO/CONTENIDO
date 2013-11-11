/* global Con: true, jQuery: true */

/**
 * FrameResize Class $Id$
 *
 * Controls the CONTENIDO frame toggling and resizing
 *
 * @module frame-resize
 * @author Jan Lengowski <Jan.Lengowski@4fb.de>, Murat Purc <murat@purc.de>
 * @copyright four for business AG <www.4fb.de>
 * @version 1.0
 *
 * $Author: timo.hummel $ $Date: 2005/08/22 12:21:18 $
 * $RCSfile: conFrameResize.js,v $
 */

(function(Con, $) {
    'use strict';

    var NAME = 'frame-resize';

    /**
     * Contenido frame resize class
     * @class  FrameResize
     * @constructor
     * @param  {String}  parentFrameId
     * @param  {String}  frameOne
     * @param  {String}  frameTwo
     * @param  {String}  frameThree
     * @param  {String}  frameFour
     */
    Con.FrameResize = function(parentFrameId, frameOne, frameTwo, frameThree, frameFour) {
        // Class correctly initialized
        this.ok = false;

        // Is the user dragging the frame
        this.drag = false;

        // Mouse cursor position
        this.x = 0;

        // Cursor offset
        this.dragDiff = 15;

        // Actual size of the frame
        this.size = 200;

        // Default frame size
        this.defaultSize = 200;

        // Optimal size (Without scrollbars)
        this.optimalSize = 200;

        // Id of the frameset, CONTENIDO default is 'contentFrame'
        this.frameSetId = parentFrameId || 'contentFrame';

        // Object reference main frameset
        this.frameSet = '';

        // References to the 4 CONTENIDO frames
        this.frames = [];

        // Name of the frames LeftTop, LeftBottom, RightTop, RightBottom
        this.frameNames = [];
        this.frameNames[1] = frameOne   || 'left_top';
        this.frameNames[2] = frameTwo   || 'left_bottom';
        this.frameNames[3] = frameThree || 'right_top';
        this.frameNames[4] = frameFour  || 'right_bottom';

        // Object reference to the right image
        this.imgRight = '';

        // Status of the frame 'hidden', 'normal' or 'drag'
        this.status = 'normal';

        // Value of the col attribute in normal pos
        this.colValHidden = '*,100%';

        // Value of the col attribute in hidden pos
        this.colValNormal = '200,100%';

        // Value of the col attribute in resizable pos
        this.colValDrag = ',100%';

        // Value of the col attribute in resizable pos
        this.colValPrevious = '';

        // Number of frames initialized
        this.count = 0;

        // Drag cell id - defaults to 'dragcell'
        this.dragCellId = 'dragcell';

        // Object reference to the drag cell
        this.dragObj = false;
    };

    Con.FrameResize.prototype = {

        /**
         * Toggle the status of the frame 'show', 'hidden'
         * @method toggle
         */
        toggle: function() {
            if (!this.ok) {
                return;
            }

            if (this.status === 'normal') {

                this.size = 0;
                this.colValPrevious = this.frameSet.cols;
                this.frameSet.cols = this.colValHidden;
                this.status = 'hidden';
                this.imgRight.style.display = '';

            } else if (this.status === 'hidden') {

                this.imgRight.style.display = 'none';

                // Resize frameset
                this.frameSet.cols = this.colValPrevious;

                // Set status to normal
                this.status = 'normal';

                // Resetting drag size
                this.dragSize = this.defaultSize;

            } else if (this.status === 'dragged') {

                this.imgRight.style.display = 'none';

                // Resize frameset
                this.frameSet.cols = this.colValNormal;

                // Set status to normal
                this.status = 'normal';

                // Resetting drag size
                this.size = this.defaultSize;

            }

            this.frameSet.rows = this.frameSet.rows;  //IE10 bug fix
        },

        /**
         * Initialize the Class
         * @method init
         */
        init: function() {
            // Create reference to other frames with this init method because of different load times
            this.frameSet = Con.$('#' + this.frameSetId)[0];

            // Reference to the "show" image
            this.imgRight = Con.$('#toggleimage', window.frames.right[this.frameNames[3]].document)[0];

            // Class is correcty initialized
            this.ok = true;
        },

        /**
         * Init a frame with all events
         * @method initFrame
         */
        initFrame: function() {
            this.count++;

            if (this.count == 4) {
                for (i = 2; i < this.frameNames.length; i++) {
                    (function(pos, context) {
                        context.frames[i].document.onmouseover = function() {
                            //context.frameResize._stopDrag();
                        }

                        context.frames[i].document.onclick = function() {
                            //context.frameResize._stopDrag();
                        }
                    })(i, this);
                }

                this.getOptimalSize();
            }
        },

        /**
         * Sets the optimal frame size
         * @method getOptimalSize
         */
        getOptimalSize: function() {
            var refs = this.frames[2].document.getElementsByTagName("TABLE");
            if (refs.length > 0) {
                this.optimalSize = refs[0].offsetWidth;
            }
        },

        /**
         * Resize the layer while 'dragging'
         * @method dragTo
         * @param {Number}  px  Resize by this value in pixels
         */
        dragTo: function(px) {
            this.size = px;
            this.status = 'dragged';

            if (this.size < this.defaultSize) {
                // Smallest size is default size
                this.size = this.defaultSize;
            }

            this.frameSet.cols = this.size + this.colValDrag;
        },

        /**
         * Set mouse position capturing for netscape and mozilla.
         * @method captureMousePosition
         */
        captureMousePosition: function() {
            var context = this.frames[1];

            // Capture events
            context.document.captureEvents(Event.MOUSEMOVE);

            // Capture mouse position
            context.document.onmousemove = function(e) {
                context.frameResize.x = e.pageX;

                if (context.frameResize.drag) {
                    context.frameResize.dragTo(frameResize.dragDiff + frameResize.x);
                }

                return true;
            }

            // Leave drag mode
            context.document.onmouseup = function() {
                context.frameResize.drag = false;
                context.frameResize.dragPosX = 0;
            }
        },

        /**
         * Set the Drag events
         * @method setDragEvents
         * @param {String}  id  Id of the Drag Element
         */
        setDragEvents: function(id) {
            if (Con.isNs) {
                this.dragObj = this.frames[1].document.getElementById(id);

                this.dragObj.onmouseover = function() {
                    this.style.cursor = 'hand';
                };

                this.dragObj.onmousedown = function() {
                    this.style.cursor = 'move';

                    frameResize.drag = true;
                    frameResize.dragPosX = frameResize.x;
                    frameResize.dragDiff = frameResize.size - frameResize.dragPosX;
                };

                this.dragObj.onmouseup = function() {
                    this.style.cursor = 'default';

                    frameResize.drag = false;

                    if (frameResize.x == frameResize.dragPosX) {
                        frameResize.toggle();
                    }
                };
            }

            if (Con.isMsie) {
                this.dragObj = window.frames[this.frameNames[1]].document.getElementById(id);

                this.dragObj.onmouseover = function() {
                    this.style.cursor = 'hand';
                };

                this.dragObj.onclick = function() {
                    frameResize.toggle();
                };
            }
        },

        /**
         * Leave drag mode
         * @method _stopDrag
         * @private
         */
        _stopDrag: function() {
            this.drag = false;
        },

        /**
         * Calc total height of given objects
         * @method totalHeight
         * @param  {HTMLElement[]}  elements
         * @return  {Number}
         */
        totalHeight: function(elements) {
            var sum = 0;
            for (var i = 0; i < elements.length; i++) {
                sum += elements[i].offsetHeight;
            }
            return sum;
        },

        /**
         * Resize framestructure for top left
         * @method resizeTopLeftFrame
         * @param  {Number}  height
         */
        resizeTopLeftFrame: function(height) {
            try {
                var framesetLeft = $('#framesetleft', Con.getFrame('left').document)[0],
                    framesetDimension = framesetLeft.rows,
                    dimensionList = framesetDimension.split(',');
                framesetLeft.rows = dimensionList[0] + "," + height + "," + dimensionList[2];
            } catch (e) {
                Con.log(["resizeTopLeftFrame: Resize failed", e], "FrameResize", "warn");
            }
        }

    };

    // @deprecated [2013-10-21] Assign to windows scope (downwards compatibility)
    window.conFrameResize = Con.FrameResize;

})(Con, Con.$);
