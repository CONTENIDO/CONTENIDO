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
class NumberedViewFactory implements ViewFactory {
	public View create(Element elem) {
		String kind = elem.getName();
		if (kind != null)
			if (kind.equals(AbstractDocument.ContentElementName)) {
				return new LabelView(elem);
			}
			else if (kind.equals(AbstractDocument.
							 ParagraphElementName)) {
				return new NumberedParagraphView(elem);
			}
			else if (kind.equals(AbstractDocument.
					 SectionElementName)) {
				return new BoxView(elem, View.Y_AXIS);
			}
			else if (kind.equals(StyleConstants.
					 ComponentElementName)) {
				return new ComponentView(elem);
			}
			else if (kind.equals(StyleConstants.IconElementName)) {
				return new IconView(elem);
			}
		// default to text display
		return new LabelView(elem);
	}
}
