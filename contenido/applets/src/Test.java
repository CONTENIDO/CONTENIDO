/*
 * Created on 21-Mar-2004
 *
 * To change the template for this generated file go to
 * Window - Preferences - Java - Code Generation - Code and Comments
 */
import javax.swing.*;
import java.awt.Font;

/**
 * @author timo.hummel
 *
 * To change the template for this generated type comment go to
 * Window - Preferences - Java - Code Generation - Code and Comments
 */
public class Test extends JApplet {
	
	JEditorPane edit;
	
	public Test() {
		edit = new TextEditorPane();
		edit.setEditorKit(new NumberedEditorKit());

		JScrollPane scroll = new JScrollPane(edit);
		getContentPane().add(scroll);
		setSize(300, 300);
		edit.setFont(new Font("Monospaced",0,12));
		setVisible(true);
	}
	
	public String getText ()
	{
		return edit.getText();
	}
	
	public void setText (String text)
	{
		edit.setText(text);
	}	

}







