/*
 * Created on 21-Mar-2004
 *
 * To change the template for this generated file go to
 * Window - Preferences - Java - Code Generation - Code and Comments
 */

import javax.swing.text.*;


/**
 * @author timo.hummel
 *
 * To change the template for this generated type comment go to
 * Window - Preferences - Java - Code Generation - Code and Comments
 */
class NumberedEditorKit extends StyledEditorKit {
	public ViewFactory getViewFactory() {
		return new NumberedViewFactory();
	}
}