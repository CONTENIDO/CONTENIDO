/**
 * conFrameResize Class $Id: conFrameResize.js,v 1.6 2005/08/22 12:21:18 timo.hummel Exp $
 *
 * Controls the Contenido frame toggling and resizing
 *
 * @author Jan Lengowski <Jan.Lengowski@4fb.de>
 * @copyright four for business AG <www.4fb.de>
 * @version 0.9
 *
 * $Author: timo.hummel $ $Date: 2005/08/22 12:21:18 $
 * $RCSfile: conFrameResize.js,v $
 */
function conFrameResize(parentFrameId, frameOne, frameTwo, frameThree, frameFour)
{
    /* Class correctly initialized */
    this.ok = false;
    
    /* Is the user dragging the frame */
    this.drag = false;

    /* Mouse cursor position */
    this.x = 0;
    
    /* Cursor offset */
    this.dragDiff = 15;

    /* Actual size of the frame */
    this.size = 200;

    /* Default frame size */
    this.defaultSize = 200;
    
    /* Optimal size (Without scrollbars) */
    this.optimalSize = 200;
    
    /* Id of the frameset, contenido default
       is 'contentFrame' */
    this.frameSetId = parentFrameId || 'contentFrame';

    /* Object reference main frameset */
    this.frameSet = '';

   /* References to the 4 Contenido frames */
    this.frames = [];

    /* Name of the frames
       LeftTop, LeftBottom, RightTop, RightBottom */
    this.frameNames = [];
    this.frameNames[1] = frameOne   || 'left_top';
    this.frameNames[2] = frameTwo   || 'left_bottom';
    this.frameNames[3] = frameThree || 'right_top';
    this.frameNames[4] = frameFour  || 'right_bottom';

    /* Object reference to the left image */
    this.imgLeft = document.getElementById( 'toggleImage' );
    
    /* Left image source */
    this.imgLeftSrc = 'images/toggle_frame_left.gif';
    
    /* Right image source */
    this.imgRightSrc = 'images/toggle_frame_right.gif';
    
    /* Neutral image src */
    this.spacerImage = 'images/spacer.gif'
    
    /* Object reference to the right image */
    this.imgRight = '';
    
    /* Status of the frame 'hidden', 'normal' or 'drag' */
    this.status = 'normal';

    /* Value of the col attribute in normal pos */
    this.colValHidden = '*,100%';
    
    /* Value of the col attribute in hidden pos */
    this.colValNormal = '200,100%';
    
    /* Value of the col attribute in resizable pos */
    this.colValDrag = ',100%';
    
    /* Value of the col attribute in resizable pos */
    this.colValPrevious = '';    

    /* Number of frames initialized */
    this.count = 0;

    /* Drag cell id - defaults to 'dragcell' */
    this.dragCellId = 'dragcell';
    
    /* Object reference to the drag cell */
    this.dragObj = false;
}

/**
 * Toggle the status of the frame 'show', 'hidden'
 *
 * @param none
 * @return none
 */
conFrameResize.prototype.toggle = function()
{
    if (this.ok)
    {
        if (this.status == 'normal')
        {
					this.size = 0;
					this.colValPrevious = this.frameSet.cols;
					this.frameSet.cols = this.colValHidden;
					this.imgRight.src = this.imgRightSrc;
					this.imgRight.style.cursor = "pointer";
					this.status = 'hidden';
        } 
				else if (this.status == 'hidden') 
				{
					/* Change image sources */
					this.imgRight.src = this.spacerImage;

					/* Cursor style */
					this.imgRight.style.cursor = "default";

					/* Resize frameset */
					this.frameSet.cols = this.colValPrevious;

					/* Set status to normal */
					this.status = 'normal';

					/* Resetting drag size */
					this.dragSize = this.defaultSize;
        } 
				else if (this.status == 'dragged') 
				{
					/* Change image sources */
					this.imgRight.src = this.spacerImage;

					/* Cursor style */
					this.imgRight.style.cursor = "default";

					/* Resize frameset */
					this.frameSet.cols = this.colValNormal;

					/* Set status to normal */
					this.status = 'normal';

					/* Resetting drag size */
					this.size = this.defaultSize;
        }
    }
}

/**
 * Initialize the Class
 *
 * @param none
 * @return void
 */
conFrameResize.prototype.init = function()
{
    /* Create reference to other frames with this
       init method because of different load times */
    this.frameSet = document.getElementById(this.frameSetId);
    
    /* Reference to the "show" image */
    this.imgRight = window.frames["right"][this.frameNames[3]].document.getElementById('toggleimage');

    // Class is correcty initialized
    this.ok = true;
}

/**
 * Init a frame with all events
 * @param none
 * @return none
 */
conFrameResize.prototype.initFrame = function()
{
    this.count ++;

    if (this.count == 4)
    {
        for (i=2; i<this.frameNames.length; i++)
        {
            with (this.frames[i])
            {
                document.onmouseover = function()
                {
                    //frameResize.stopDrag();
                }

                document.onclick = function()
                {
                    //frameResize.stopDrag();
                }
            }
        }

        this.getOptimalSize();
    }
}

conFrameResize.prototype.getOptimalSize = function()
{
    var refs = this.frames[2].document.getElementsByTagName("TABLE");
    if (refs.length > 0)
    {
        this.optimalSize = refs[0].offsetWidth;
    }
}


/**
 * Resize the layer while 'dragging'
 *
 * @param int Resize by this value in pixels
 * @return void
 */
conFrameResize.prototype.dragTo = function(px)
{
    this.size = px;
    this.status = 'dragged';
    
    if (this.size < this.defaultSize)
    {   // Smallest size is default size
        this.size = this.defaultSize;
    }
    
    this.frameSet.cols = this.size + this.colValDrag;
}

/**
 * Set mouse position capturing for
 * netscape and mozilla.
 *
 * @param none
 * @return none
 */
conFrameResize.prototype.captureMousePosition = function()
{
    with (this.frames[1])
    {
        // Capture events
        document.captureEvents(Event.MOUSEMOVE);

        // Capture mouse position
        document.onmousemove = function(e)
        {
            frameResize.x = e.pageX;

            if (frameResize.drag)
            {
                frameResize.dragTo(frameResize.dragDiff + frameResize.x);
            }
            
            return true;
        }

        // Leave drag mode
        document.onmouseup = function()
        {
            frameResize.drag = false;
            frameResize.dragPosX = 0;
        }
    }
}
/**
 * Set the Drag events
 *
 * @param string Id of the Drag Element
 * @return true
 */
conFrameResize.prototype.setDragEvents = function(id)
{
    if (is.NS)
    {
        this.dragObj = this.frames[1].document.getElementById(id);

        this.dragObj.onmouseover = function()
        {
            this.style.cursor = 'hand';
        }

        this.dragObj.onmousedown = function()
        {
            this.style.cursor = 'move';
            
            frameResize.drag = true;
            frameResize.dragPosX = frameResize.x;
            frameResize.dragDiff = frameResize.size - frameResize.dragPosX;            
        }

        this.dragObj.onmouseup = function()
        {
            this.style.cursor = 'default';

            frameResize.drag = false;

            if (frameResize.x == frameResize.dragPosX)
            {
                frameResize.toggle();
            }
        }
    }

    if (is.IE)
    {
        this.dragObj = window.frames[this.frameNames[1]].document.getElementById(id);
        
        this.dragObj.onmouseover = function()
        {
            this.style.cursor = 'hand';
        }
        
        this.dragObj.onclick = function()
        {
            frameResize.toggle();
        }
    }
}

/**
 * Leave drag mode
 * @param none
 * @access private
 */
conFrameResize.prototype.stopDrag = function()
{
    this.drag = false;
}

/**
 * Calc total height of given objects
 *
 **/
conFrameResize.prototype.totalHeight = function(elements)
 {
	 var sum=0;
	 for(var i=0; i<elements.length; i++)
	 {
		 sum += elements[i].offsetHeight;
	 }
	 return sum;
 }

/**
 * Resize framestructure for top left
 *
 **/
conFrameResize.prototype.resizeTopLeftFrame = function(height)
 {
	 var framesetDimension = window.frames['left'].document.getElementById('framesetleft').rows;
	 var dimensionList = framesetDimension.split(',');
	 window.frames['left'].document.getElementById('framesetleft').rows = dimensionList[0] + "," + height + "," + dimensionList[2];
 }
